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
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\EntityAnonymizerServiceInterface;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
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

    /**
     * @return array{processed: int, updated: int, propertyStats: array<string, int>}
     */
    private function anonymizeAuditLog(AnonymizeService $service, Anonymize $attribute): array
    {
        $metadata = $this->em->getClassMetadata(E2eAuditLog::class);

        return $service->anonymizeEntity($this->em, $metadata, new ReflectionClass(E2eAuditLog::class), [], 10, false, null, null, $attribute);
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
