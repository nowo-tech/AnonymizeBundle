<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Sqlite;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Event\AfterEntityAnonymizeEvent;
use Nowo\AnonymizeBundle\Event\BeforeEntityAnonymizeEvent;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\EntityAnonymizerServiceInterface;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
use Nowo\AnonymizeBundle\Service\PreFlightCheckService;
use Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity\E2eAuditLog;
use Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity\E2eCompany;
use Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity\E2eCustomer;
use Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity\E2eTempDocument;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;

use const PHP_VERSION_ID;

/**
 * AnonymizeService edge cases against a real Doctrine ORM + SQLite (in memory) schema.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class AnonymizeServiceSqliteTest extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(new AttributeDriver([__DIR__ . '/Entity']));
        if (PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        } else {
            $config->setProxyDir(sys_get_temp_dir());
            $config->setProxyNamespace('AnonymizeSqliteServiceProxies');
        }

        $this->em = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config), $config);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        foreach (['first', 'second', 'third'] as $message) {
            $this->em->persist(new E2eAuditLog($message));
        }
        $this->em->flush();
        $this->em->clear();
    }

    public function testResetClearsTheFakerCache(): void
    {
        $service  = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher());
        $getFaker = new ReflectionMethod($service, 'getFaker');
        $getFaker->invoke($service, 'email', null);

        $cache = new ReflectionProperty($service, 'fakerCache');
        self::assertNotSame([], $cache->getValue($service));

        $service->reset();

        self::assertSame([], $cache->getValue($service));
    }

    public function testUnresolvableAnonymizerServiceFailsAndRollsBack(): void
    {
        $service = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher(), null, new ServiceLocator([]));

        try {
            $this->anonymizeAuditLog($service, new Anonymize(anonymizeService: 'missing.anonymizer'));
            self::fail('Expected a RuntimeException');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('Cannot get anonymizer service "missing.anonymizer"', $e->getMessage());
        }

        self::assertFalse($this->em->getConnection()->isTransactionActive());
    }

    public function testBatchAnonymizerSkipsEmptyUnknownAndExcludedRows(): void
    {
        $anonymizer = new class implements EntityAnonymizerServiceInterface {
            public function supportsBatch(): bool
            {
                return true;
            }

            public function anonymize(EntityManagerInterface $em, ClassMetadata $metadata, array $record, bool $dryRun): array
            {
                return [];
            }

            public function anonymizeBatch(EntityManagerInterface $em, ClassMetadata $metadata, array $records, bool $dryRun): array
            {
                return [
                    0 => [],                                // nothing to update
                    7 => ['message' => 'no such row'],      // index outside the chunk
                    1 => ['message' => 'excluded'],         // row id 2 is excluded by pattern
                    2 => ['message' => 'batch anonymized'],
                ];
            }
        };
        $service = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher(), null, new ServiceLocator(['batch.anonymizer' => static fn (): object => $anonymizer]));

        $stats = $this->anonymizeAuditLog($service, new Anonymize(excludePatterns: ['id' => '2'], anonymizeService: 'batch.anonymizer'));

        self::assertSame(3, $stats['processed']);
        self::assertSame(1, $stats['updated']);
        self::assertSame(['first', 'second', 'batch anonymized'], $this->em->getConnection()->fetchFirstColumn('SELECT message FROM e2e_audit_log ORDER BY id'));
    }

    public function testTableHasAnonymizedColumnIntrospection(): void
    {
        $service = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher());
        $method  = new ReflectionMethod($service, 'tableHasAnonymizedColumn');
        $conn    = $this->em->getConnection();

        self::assertTrue($method->invoke($service, $conn, 'e2e_customer'));
        self::assertFalse($method->invoke($service, $conn, 'e2e_company'));
        self::assertFalse($method->invoke($service, $conn, 'e2e_table_that_does_not_exist'));
    }

    public function testDiscriminatorIsIgnoredWithoutValueOrColumnName(): void
    {
        $service = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher());
        $method  = new ReflectionMethod($service, 'getDiscriminatorForTruncate');

        $metadata = $this->em->getClassMetadata(E2eTempDocument::class);
        self::assertSame(['column' => 'kind', 'value' => 'temp'], $method->invoke($service, $metadata));

        $withoutValue                     = clone $metadata;
        $withoutValue->discriminatorValue = null;
        self::assertSame(['column' => null, 'value' => null], $method->invoke($service, $withoutValue));

        $withoutColumn                      = clone $metadata;
        $withoutColumn->discriminatorColumn = null;
        self::assertSame(['column' => null, 'value' => null], $method->invoke($service, $withoutColumn));
    }

    public function testConvertValueCastsByColumnTypeAndKeepsUnknownColumns(): void
    {
        $service  = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher());
        $method   = new ReflectionMethod($service, 'convertValue');
        $company  = $this->em->getClassMetadata(E2eCompany::class);
        $customer = $this->em->getClassMetadata(E2eCustomer::class);

        self::assertSame(4.5, $method->invoke($service, '4.5', $company, 'rating'));
        self::assertSame(33, $method->invoke($service, '33', $customer, 'customer_age'));
        self::assertTrue($method->invoke($service, 1, $customer, 'vip'));
        self::assertSame('kept', $method->invoke($service, 'kept', $customer, 'not_a_column'));
    }

    public function testAnonymizableTraitIsDetectedOnAnyAncestor(): void
    {
        $service = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher());
        $method  = new ReflectionMethod($service, 'usesAnonymizableTrait');

        self::assertTrue($method->invoke($service, new ReflectionClass(AnonymizeServiceSqliteTestGrandChild::class)));
        self::assertFalse($method->invoke($service, new ReflectionClass(E2eCompany::class)));
    }

    public function testPatternFieldNamesIgnoreMixedLists(): void
    {
        $service = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher());
        $method  = new ReflectionMethod($service, 'getPatternFieldNames');

        self::assertSame(['a', 'b'], $method->invoke($service, [['a' => '1'], ['b' => '2', 'a' => '3']]));
        self::assertSame([], $method->invoke($service, [['a' => '1'], 'not-a-set']));
    }

    public function testBeforeEntityEventRunsBeforeRowsAreWritten(): void
    {
        $dispatcher = new EventDispatcher();
        $seen       = [];
        $dispatcher->addListener(BeforeEntityAnonymizeEvent::class, function (BeforeEntityAnonymizeEvent $event) use (&$seen): void {
            $seen[] = [$event->getTotalRecords(), $event->isDryRun(), $this->messages()];
        });

        $stats = $this->anonymizeAuditLog($this->serviceWithRowAnonymizer($dispatcher), new Anonymize(anonymizeService: 'row.anonymizer'));

        self::assertSame([[3, false, ['first', 'second', 'third']]], $seen);
        self::assertSame(3, $stats['updated']);
        self::assertSame(['anon 1', 'anon 2', 'anon 3'], $this->messages());
    }

    public function testBeforeEntityEventIsDispatchedForAnEmptyTable(): void
    {
        $this->em->getConnection()->executeStatement('DELETE FROM e2e_audit_log');
        $dispatcher = new EventDispatcher();
        $events     = [];
        $dispatcher->addListener(BeforeEntityAnonymizeEvent::class, static function (BeforeEntityAnonymizeEvent $event) use (&$events): void {
            $events[] = $event;
        });
        $dispatcher->addListener(AfterEntityAnonymizeEvent::class, static function (AfterEntityAnonymizeEvent $event) use (&$events): void {
            $events[] = $event;
        });

        $stats = $this->anonymizeAuditLog($this->serviceWithRowAnonymizer($dispatcher), new Anonymize(anonymizeService: 'row.anonymizer'));

        self::assertSame(['processed' => 0, 'updated' => 0, 'propertyStats' => []], $stats);
        self::assertCount(2, $events);
        self::assertInstanceOf(BeforeEntityAnonymizeEvent::class, $events[0]);
        self::assertSame(0, $events[0]->getTotalRecords());
        self::assertInstanceOf(AfterEntityAnonymizeEvent::class, $events[1]);
    }

    public function testThrowingBeforeEntityListenerLeavesTheClassUntouched(): void
    {
        $dispatcher = new EventDispatcher();
        $after      = 0;
        $dispatcher->addListener(BeforeEntityAnonymizeEvent::class, static function (): never {
            throw new RuntimeException('listener veto');
        });
        $dispatcher->addListener(AfterEntityAnonymizeEvent::class, static function () use (&$after): void {
            ++$after;
        });

        try {
            $this->anonymizeAuditLog($this->serviceWithRowAnonymizer($dispatcher), new Anonymize(anonymizeService: 'row.anonymizer'));
            self::fail('Expected the listener exception');
        } catch (RuntimeException $e) {
            self::assertSame('listener veto', $e->getMessage());
        }

        self::assertSame(['first', 'second', 'third'], $this->messages());
        self::assertFalse($this->em->getConnection()->isTransactionActive());
        self::assertSame(0, $after);
    }

    public function testBeforeEntityListenerLeavingATransactionOpenAbortsTheRun(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(BeforeEntityAnonymizeEvent::class, static function (BeforeEntityAnonymizeEvent $event): void {
            $event->getEntityManager()->getConnection()->beginTransaction();
        });
        $connection = $this->em->getConnection();

        try {
            $this->anonymizeAuditLog($this->serviceWithRowAnonymizer($dispatcher), new Anonymize(anonymizeService: 'row.anonymizer'));
            self::fail('Expected a RuntimeException');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('a BeforeEntityAnonymizeEvent listener changed the transaction nesting level from 0 to 1', $e->getMessage());
            self::assertStringContainsString(E2eAuditLog::class, $e->getMessage());
        }

        self::assertSame(1, $connection->getTransactionNestingLevel());
        $connection->rollBack();
        self::assertSame(['first', 'second', 'third'], $this->messages());
    }

    public function testAnonymizerLeavingATransactionOpenFailsInsteadOfReportingUpdates(): void
    {
        $anonymizer = new class implements EntityAnonymizerServiceInterface {
            private bool $opened = false;

            public function supportsBatch(): bool
            {
                return false;
            }

            public function anonymize(EntityManagerInterface $em, ClassMetadata $metadata, array $record, bool $dryRun): array
            {
                if (!$this->opened) {
                    $this->opened = true;
                    $em->getConnection()->beginTransaction();
                }

                return ['message' => 'anon ' . $record['id']];
            }

            public function anonymizeBatch(EntityManagerInterface $em, ClassMetadata $metadata, array $records, bool $dryRun): array
            {
                return [];
            }
        };
        $dispatcher = new EventDispatcher();
        $after      = 0;
        $dispatcher->addListener(AfterEntityAnonymizeEvent::class, static function () use (&$after): void {
            ++$after;
        });
        $service = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher(), $dispatcher, new ServiceLocator(['leaky.anonymizer' => static fn (): object => $anonymizer]));

        try {
            $this->anonymizeAuditLog($service, new Anonymize(anonymizeService: 'leaky.anonymizer'));
            self::fail('Expected a RuntimeException');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('an AnonymizePropertyEvent listener or an entity anonymizer service changed the transaction nesting level from 0 to 1', $e->getMessage());
        }

        self::assertSame(0, $after);
        $this->em->getConnection()->rollBack();
        self::assertSame(['first', 'second', 'third'], $this->messages());
    }

    /**
     * LQ-N-L1 on the attribute path: entity- and property-level pattern keys must be columns of the table, the
     * discriminator column, a to-one join column, or "association.field" of a joinable to-one relation.
     */
    public function testPreFlightRejectsUnknownPatternFieldsOnAttributes(): void
    {
        $preFlight = new PreFlightCheckService(new FakerFactory('en_US'));
        $customer  = [
            'metadata'   => $this->em->getClassMetadata(E2eCustomer::class),
            'reflection' => new ReflectionClass(E2eCustomer::class),
            'attribute'  => new Anonymize(includePatterns: [['company.name' => 'Acme%', 'company_id' => '1'], ['stauts' => 'active']], excludePatterns: ['company.nme' => 'x', 'email.domain' => 'y']),
        ];
        $document = [
            'metadata'   => $this->em->getClassMetadata(E2eTempDocument::class),
            'reflection' => new ReflectionClass(E2eTempDocument::class),
            'attribute'  => new Anonymize(includePatterns: ['kind' => 'temp']),
        ];

        $errors = array_values($preFlight->performChecks($this->em, [E2eCustomer::class => $customer, E2eTempDocument::class => $document]));

        $unknown = array_values(array_filter($errors, static fn (string $e): bool => str_starts_with($e, 'Unknown field')));
        self::assertCount(3, $unknown, implode("\n", $errors));
        self::assertStringContainsString('Unknown field "stauts" in the include patterns of ' . E2eCustomer::class, $unknown[0]);
        self::assertStringContainsString('"company.nme" in the exclude patterns', $unknown[1]);
        self::assertStringContainsString('"email.domain" in the exclude patterns', $unknown[2]);
        self::assertStringContainsString('table "e2e_customer"', $unknown[0]);
    }

    /**
     * @return array{processed: int, updated: int, propertyStats: array<string, int>}
     */
    private function anonymizeAuditLog(AnonymizeService $service, Anonymize $attribute): array
    {
        $metadata = $this->em->getClassMetadata(E2eAuditLog::class);

        return $service->anonymizeEntity($this->em, $metadata, new ReflectionClass(E2eAuditLog::class), [], 10, false, null, null, $attribute);
    }

    private function serviceWithRowAnonymizer(EventDispatcher $dispatcher): AnonymizeService
    {
        $anonymizer = new class implements EntityAnonymizerServiceInterface {
            public function supportsBatch(): bool
            {
                return false;
            }

            public function anonymize(EntityManagerInterface $em, ClassMetadata $metadata, array $record, bool $dryRun): array
            {
                return ['message' => 'anon ' . $record['id']];
            }

            public function anonymizeBatch(EntityManagerInterface $em, ClassMetadata $metadata, array $records, bool $dryRun): array
            {
                return [];
            }
        };

        return new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher(), $dispatcher, new ServiceLocator(['row.anonymizer' => static fn (): object => $anonymizer]));
    }

    /**
     * @return list<string>
     */
    private function messages(): array
    {
        /* @var list<string> */
        return $this->em->getConnection()->fetchFirstColumn('SELECT message FROM e2e_audit_log ORDER BY id');
    }
}

/**
 * @internal
 */
abstract class AnonymizeServiceSqliteTestBase
{
    use AnonymizableTrait;
}

/**
 * @internal
 */
abstract class AnonymizeServiceSqliteTestChild extends AnonymizeServiceSqliteTestBase
{
}

/**
 * @internal
 */
final class AnonymizeServiceSqliteTestGrandChild extends AnonymizeServiceSqliteTestChild
{
}
