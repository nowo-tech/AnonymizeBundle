<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Equivalence;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Command\AnonymizeCommand;
use Nowo\AnonymizeBundle\Event\AfterAnonymizeEvent;
use Nowo\AnonymizeBundle\Event\AfterEntityAnonymizeEvent;
use Nowo\AnonymizeBundle\Event\AnonymizePropertyEvent;
use Nowo\AnonymizeBundle\Event\BeforeAnonymizeEvent;
use Nowo\AnonymizeBundle\Event\BeforeEntityAnonymizeEvent;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Metadata\ConfigAnonymizeMetadataProvider;
use Nowo\AnonymizeBundle\Service\AnonymizationHistoryService;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\EnvironmentProtectionService;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
use Nowo\AnonymizeBundle\Service\PreFlightCheckService;
use Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer\CallLog;
use Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer\DualPatientAnonymizer;
use Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer\LegacyPatientAnonymizer;
use Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer\PatientRedaction;
use Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer\RowPatientAnonymizer;
use Nowo\AnonymizeBundle\Tests\Integration\Ddd\Domain\ContactDetails;
use Nowo\AnonymizeBundle\Tests\Integration\Ddd\Domain\Patient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

use function array_keys;
use function sprintf;

use const PHP_VERSION_ID;

/**
 * 1.6 equivalence of the deprecated and the ORM-free paths on fixture data (data-destructive tool: the anonymized rows
 * and columns must be exactly those of 1.5, with no extra and no missing cell).
 *
 * Every scenario runs `nowo:anonymize:run` (environment guard, pre-flight, --dry-run / --force, --batch-size 2, one
 * transaction per chunk) on a fresh SQLite copy of the same seven XML-mapped patients and compares the whole table,
 * column by column:
 *
 * - entity anonymizer service: deprecated EntityAnonymizerServiceInterface vs RowAnonymizerInterface vs a service on
 *   both (row by row and by batch);
 * - property path: an AnonymizePropertyEvent listener on the deprecated getters vs one on the scalar accessors.
 *
 * Dry runs leave every cell unchanged and see the same calls, records and planned values as the real runs.
 */
final class RowAnonymizerEquivalenceTest extends TestCase
{
    private const SECRET = 'REDACTED-TEST-equivalence-db-password';

    /** Seed: id => [email, full name, status, phone, city]. */
    private const PATIENTS = [
        'p-1' => ['ana@example.test', 'Ana Real', 'active', '+34 600 000 001', 'Madrid'],
        'p-2' => ['kept@example.test', 'Kept Real', 'keep', '+34 600 000 002', 'Bilbao'],
        'p-3' => ['skip@example.test', 'Skip Real', 'skip-service', '+34 600 000 003', 'Sevilla'],
        'p-4' => ['nophone@example.test', 'No Phone', 'active', null, 'Valencia'],
        'p-5' => ['eva@example.test', 'Eva Real', 'active', '+34 600 000 005', null],
        'p-6' => ['kept2@example.test', 'Kept Two', 'keep', '+34 600 000 006', 'Vigo'],
        'p-7' => ['luis@example.test', 'Luis Real', 'active', '+34 600 000 007', 'Cádiz'],
    ];

    /** Rows the bundle may change: not excluded by the entity pattern and not skipped by the service / listener. */
    private const CHANGED_IDS = ['p-1', 'p-4', 'p-5', 'p-7'];

    private string $tempDir;

    /** @var list<EntityManager> */
    private array $managers = [];

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/anonymize_equivalence_' . uniqid();
        mkdir($this->tempDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        foreach ($this->managers as $em) {
            $em->close();
        }
        $this->managers = [];
        $this->removeDirectory($this->tempDir);
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function batchModes(): iterable
    {
        yield 'row by row' => [false];
        yield 'by batch' => [true];
    }

    #[DataProvider('batchModes')]
    #[IgnoreDeprecations]
    public function testServicePathsWriteExactlyTheSameCells(bool $batch): void
    {
        $original = $this->snapshot($this->seededManager());

        $runs = [];
        foreach ($this->anonymizers($batch) as $name => $anonymizer) {
            $em          = $this->seededManager();
            $runs[$name] = $this->runCommand($em, $this->serviceConfig(), $anonymizer, dryRun: false) + ['snapshot' => $this->snapshot($em), 'log' => $anonymizer->log->calls];
        }

        $legacy = $runs['legacy'];
        self::assertSame(0, $legacy['exitCode'], $legacy['output']);
        self::assertSame($this->expectedServiceSnapshot($original), $legacy['snapshot'], 'the deprecated path writes exactly the expected cells');
        foreach (['row', 'dual'] as $name) {
            self::assertSame(0, $runs[$name]['exitCode'], $runs[$name]['output']);
            self::assertSame($legacy['snapshot'], $runs[$name]['snapshot'], $name . ': same rows, same columns, same values as the deprecated path');
            self::assertSame($legacy['log'], $runs[$name]['log'], $name . ': same chunks, records and returned updates');
            self::assertSame($legacy['events'], $runs[$name]['events'], $name . ': same per-class and per-run statistics');
        }
        self::assertSame(['processed' => 7, 'updated' => 4], $legacy['events']['afterEntity']);
        self::assertCount($batch ? 4 : 5, $legacy['log'], $batch ? 'four chunks of at most two rows' : 'the five rows the entity pattern keeps');
    }

    #[DataProvider('batchModes')]
    #[IgnoreDeprecations]
    public function testServiceDryRunsChangeNothingAndPlanTheSameCells(bool $batch): void
    {
        $original = $this->snapshot($this->seededManager());

        $dry  = [];
        $real = [];
        foreach ($this->anonymizers($batch) as $name => $anonymizer) {
            $em         = $this->seededManager();
            $dry[$name] = $this->runCommand($em, $this->serviceConfig(), $anonymizer, dryRun: true) + ['snapshot' => $this->snapshot($em), 'log' => $anonymizer->log->calls];

            $realAnonymizer = $this->anonymizers($batch)[$name];
            $real[$name]    = $this->runCommand($this->seededManager(), $this->serviceConfig(), $realAnonymizer, dryRun: false) + ['log' => $realAnonymizer->log->calls];
        }

        foreach ($dry as $name => $run) {
            self::assertSame(0, $run['exitCode'], $run['output']);
            self::assertSame($original, $run['snapshot'], $name . ': a dry run changes no cell');
            self::assertSame($dry['legacy']['log'], $run['log'], $name . ': same dry-run calls as the deprecated path');
            self::assertSame($dry['legacy']['events'], $run['events'], $name . ': same dry-run statistics');
            self::assertNotSame([], $run['log']);
            foreach ($run['log'] as $call) {
                self::assertTrue($call['dryRun'], $name . ': the service is told it is a dry run');
            }
            // The dry run reads the same rows and plans the same values as the real run.
            self::assertSame($this->withoutDryRunFlag($real[$name]['log']), $this->withoutDryRunFlag($run['log']), $name);
            self::assertSame($real[$name]['events']['afterEntity'], $run['events']['afterEntity'], $name . ': a dry run counts what the real run updates');
        }
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function dryRunModes(): iterable
    {
        yield 'real run' => [false];
        yield 'dry run' => [true];
    }

    #[DataProvider('dryRunModes')]
    #[IgnoreDeprecations]
    public function testPropertyListenersOnDeprecatedAndScalarAccessorsWriteTheSameCells(bool $dryRun): void
    {
        $original = $this->snapshot($this->seededManager());

        $runs = [];
        foreach (['legacy' => true, 'scalar' => false] as $name => $deprecatedGetters) {
            $em          = $this->seededManager();
            $plan        = [];
            $listener    = $this->propertyListener($deprecatedGetters, $plan);
            $runs[$name] = $this->runCommand($em, $this->propertyConfig(), null, $dryRun, $listener) + ['snapshot' => $this->snapshot($em), 'plan' => $plan];
        }

        self::assertSame(0, $runs['legacy']['exitCode'], $runs['legacy']['output']);
        self::assertSame(0, $runs['scalar']['exitCode'], $runs['scalar']['output']);
        self::assertSame($dryRun ? $original : $this->expectedPropertySnapshot($original), $runs['legacy']['snapshot']);
        self::assertSame($runs['legacy']['snapshot'], $runs['scalar']['snapshot'], 'same rows, same columns, same values');
        self::assertSame($runs['legacy']['plan'], $runs['scalar']['plan'], 'the listeners saw the same table, property, column and value');
        self::assertSame($runs['legacy']['events'], $runs['scalar']['events']);
        self::assertCount(11, $runs['legacy']['plan'], 'three properties of four rows, minus the NULL phone of p-4 (never anonymized)');
    }

    /**
     * The events the run dispatches never show the entity manager (nor its connection password) when dumped.
     */
    #[IgnoreDeprecations]
    public function testDispatchedEventsDoNotExposeTheEntityManagerWhenDumped(): void
    {
        $em         = $this->seededManager();
        $dispatcher = new EventDispatcher();
        $events     = [];
        foreach ([BeforeAnonymizeEvent::class, BeforeEntityAnonymizeEvent::class, AnonymizePropertyEvent::class, AfterEntityAnonymizeEvent::class, AfterAnonymizeEvent::class] as $class) {
            $dispatcher->addListener($class, static function (object $event) use (&$events): void {
                $events[] = $event;
            });
        }
        $this->runCommand($em, $this->propertyConfig(), null, false, null, $dispatcher);

        self::assertCount(5, array_unique(array_map(static fn (object $event): string => $event::class, $events)));
        foreach ($events as $event) {
            foreach ($this->dumps($event) as $dump) {
                self::assertStringNotContainsString(self::SECRET, $dump, $event::class);
                self::assertStringNotContainsString('EntityManager', $dump, $event::class);
            }
            // The deprecated getter still returns the run's entity manager to standard hosts.
            if (method_exists($event, 'getEntityManager')) {
                self::assertSame($em, $event->getEntityManager());
            }
        }
    }

    /**
     * @return list<string>
     */
    private function dumps(object $value): array
    {
        $dumper = new CliDumper();
        $dumper->setColors(false);

        return [
            (string) $dumper->dump((new VarCloner())->cloneVar($value), true),
            var_export($value, true),
            print_r($value, true),
            print_r((array) $value, true),
            (string) json_encode($value),
            serialize($value),
        ];
    }

    /**
     * @return array{legacy: LegacyPatientAnonymizer, row: RowPatientAnonymizer, dual: DualPatientAnonymizer}
     */
    private function anonymizers(bool $batch): array
    {
        return [
            'legacy' => new LegacyPatientAnonymizer(new CallLog(), $batch),
            'row'    => new RowPatientAnonymizer(new CallLog(), $batch),
            'dual'   => new DualPatientAnonymizer(new CallLog(), $batch),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function serviceConfig(): array
    {
        return [Patient::class => [
            'exclude_patterns'  => ['status' => 'keep'],
            'anonymize_service' => 'test.patient_anonymizer',
            // The service path never sets the flag (1.5 behaviour, kept): no extra "anonymized" cell.
            'mark_anonymized' => true,
        ]];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function propertyConfig(): array
    {
        return [Patient::class => [
            'exclude_patterns' => ['status' => 'keep'],
            'mark_anonymized'  => true,
            'properties'       => [
                'email'         => ['type' => 'constant', 'weight' => 1, 'options' => ['value' => 'faker-email']],
                'fullName'      => ['type' => 'constant', 'options' => ['value' => 'faker-name']],
                'contact.phone' => ['type' => 'constant', 'options' => ['value' => 'faker-phone']],
            ],
        ]];
    }

    /**
     * Skips the rows with status "skip-service"; otherwise writes "{table}|{property}|{column}|{original}".
     *
     * @param list<array{string, string, string, string, mixed, bool}> $plan
     *
     * @return callable(AnonymizePropertyEvent): void
     */
    private function propertyListener(bool $deprecatedGetters, array &$plan): callable
    {
        return static function (AnonymizePropertyEvent $event) use ($deprecatedGetters, &$plan): void {
            if ($event->getRecord()['status'] === 'skip-service') {
                $event->setSkipAnonymization(true);

                return;
            }
            $table    = $deprecatedGetters ? $event->getMetadata()->getTableName() : $event->getTableName();
            $property = $deprecatedGetters ? $event->getProperty()->getName() : $event->getPropertyName();
            $value    = sprintf('%s|%s|%s|%s', $table, $property, $event->getColumnName(), (string) $event->getOriginalValue());
            $event->setAnonymizedValue($value);
            $plan[] = [$table, (string) $event->getRecord()['id'], $property, $event->getColumnName(), $value, $event->isDryRun()];
        };
    }

    /**
     * @param array<string, array<string, mixed>> $original
     *
     * @return array<string, array<string, mixed>>
     */
    private function expectedServiceSnapshot(array $original): array
    {
        $expected = $original;
        foreach (self::CHANGED_IDS as $id) {
            foreach (PatientRedaction::COLUMNS as $column) {
                $expected[$id][$column] = sprintf('anon:ddd_patient:%s:%s', $column, $id);
            }
        }

        return $expected;
    }

    /**
     * @param array<string, array<string, mixed>> $original
     *
     * @return array<string, array<string, mixed>>
     */
    private function expectedPropertySnapshot(array $original): array
    {
        $expected = $original;
        foreach (self::CHANGED_IDS as $id) {
            $expected[$id]['email']     = sprintf('ddd_patient|email|email|%s', $original[$id]['email']);
            $expected[$id]['full_name'] = sprintf('ddd_patient|fullName|full_name|%s', $original[$id]['full_name']);
            if ($original[$id]['contact_phone'] !== null) {
                $expected[$id]['contact_phone'] = sprintf('ddd_patient|phone|contact_phone|%s', $original[$id]['contact_phone']);
            }
            $expected[$id]['anonymized'] = 1;
        }

        return $expected;
    }

    /**
     * @param list<array{method: string, table: string, dryRun: bool, input: array<mixed>, output: array<mixed>}> $log
     *
     * @return list<array{method: string, table: string, input: array<mixed>, output: array<mixed>}>
     */
    private function withoutDryRunFlag(array $log): array
    {
        return array_map(static function (array $call): array {
            unset($call['dryRun']);

            return $call;
        }, $log);
    }

    /**
     * Every column of every row, keyed by id (integers normalised, so SQLite booleans compare as 0 / 1).
     *
     * @return array<string, array<string, mixed>>
     */
    private function snapshot(EntityManager $em): array
    {
        $rows = [];
        foreach ($em->getConnection()->fetchAllAssociative('SELECT * FROM ddd_patient ORDER BY id') as $row) {
            ksort($row);
            if (isset($row['anonymized'])) {
                $row['anonymized'] = (int) $row['anonymized'];
            }
            $rows[(string) $row['id']] = $row;
        }

        return $rows;
    }

    private function seededManager(): EntityManager
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(new SimplifiedXmlDriver([__DIR__ . '/../Ddd/config/doctrine' => 'Nowo\\AnonymizeBundle\\Tests\\Integration\\Ddd\\Domain']));
        if (PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        } else {
            $config->setProxyDir($this->tempDir . '/proxies');
            $config->setProxyNamespace('AnonymizeEquivalenceProxies');
            $config->setAutoGenerateProxyClasses(true);
        }

        $em = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true, 'user' => 'anonymizer', 'password' => self::SECRET], $config), $config);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
        foreach (self::PATIENTS as $id => [$email, $name, $status, $phone, $city]) {
            $em->persist(new Patient($id, $email, $name, $status, new ContactDetails($phone, $city)));
        }
        $em->flush();
        $em->clear();
        $this->managers[] = $em;

        return $em;
    }

    /**
     * @param array<string, array<string, mixed>> $entities
     * @param callable(AnonymizePropertyEvent): void|null $propertyListener
     *
     * @return array{exitCode: int, output: string, events: array{afterEntity: array{processed: int, updated: int}|null, after: array{processed: int, updated: int}|null}}
     */
    private function runCommand(EntityManager $em, array $entities, ?object $anonymizer, bool $dryRun, ?callable $propertyListener = null, ?EventDispatcher $dispatcher = null): array
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->willReturn($em);

        $dispatcher ??= new EventDispatcher();
        $events = ['afterEntity' => null, 'after' => null];
        $dispatcher->addListener(AfterEntityAnonymizeEvent::class, static function (AfterEntityAnonymizeEvent $event) use (&$events): void {
            $events['afterEntity'] = ['processed' => $event->getProcessed(), 'updated' => $event->getUpdated()];
        });
        $dispatcher->addListener(AfterAnonymizeEvent::class, static function (AfterAnonymizeEvent $event) use (&$events): void {
            $events['after'] = ['processed' => $event->getTotalProcessed(), 'updated' => $event->getTotalUpdated()];
        });
        if ($propertyListener !== null) {
            $dispatcher->addListener(AnonymizePropertyEvent::class, $propertyListener);
        }

        $locator  = new ServiceLocator($anonymizer === null ? [] : ['test.patient_anonymizer' => static fn (): object => $anonymizer]);
        $provider = new ConfigAnonymizeMetadataProvider($entities);
        $command  = new AnonymizeCommand(
            new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher(), $dispatcher, $locator, $provider),
            new PreFlightCheckService(new FakerFactory('en_US'), $provider),
            new AnonymizationHistoryService($this->tempDir . '/history'),
            new EnvironmentProtectionService(new ParameterBag(['kernel.environment' => 'test', 'kernel.project_dir' => $this->tempDir]), []),
            $doctrine,
            new ParameterBag(['nowo_anonymize.stats_output_dir' => '%kernel.project_dir%/var/stats']),
            $this->tempDir,
            $dispatcher,
        );

        $input = new ArrayInput($dryRun ? ['--dry-run' => true, '--batch-size' => '2'] : ['--force' => true, '--batch-size' => '2']);
        $input->setInteractive(false);
        $output   = new BufferedOutput();
        $exitCode = $command->run($input, $output);
        $em->clear();

        self::assertSame([Patient::class], array_keys($entities));

        return ['exitCode' => $exitCode, 'output' => $output->fetch(), 'events' => $events];
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff((array) scandir($dir), ['.', '..']) as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
