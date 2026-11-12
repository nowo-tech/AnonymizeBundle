<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Equivalence;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Command\AnonymizeCommand;
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
use Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer\RowPatientAnonymizer;
use Nowo\AnonymizeBundle\Tests\Integration\Equivalence\Entity\EqCompany;
use Nowo\AnonymizeBundle\Tests\Integration\Equivalence\Entity\EqPerson;
use Nowo\AnonymizeBundle\Tests\Integration\Equivalence\Entity\EqTicket;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ServiceLocator;

use function sprintf;

use const PHP_VERSION_ID;

/**
 * Audit MY-A-1: equivalence of EntityAnonymizerServiceInterface (deprecated), RowAnonymizerInterface and a service on
 * both, in the paths the first equivalence test does not reach. One `nowo:anonymize:run --batch-size 2` per scenario,
 * whole tables compared column by column, real and dry runs, row by row and by batch:
 *
 * - composite primary key (tenant, number): LIMIT / OFFSET paging, entity exclude pattern hitting rows in the middle of
 *   chunks, `mark_anonymized: true` with a service (the service path never sets the flag, as in 1.5);
 * - single-table inheritance: only the selected child's rows (discriminator) reach the service, with an include and an
 *   exclude pattern that drop rows inside chunks; the sibling class's rows in the same table stay untouched.
 */
final class RowAnonymizerEdgeCasesEquivalenceTest extends TestCase
{
    /** tenant, number, subject, requester, status (ordered by the primary key: chunks acme1+acme2, acme3+beta1, …). */
    private const TICKETS = [
        ['acme', 1, 'Printer', 'ana@example.test', 'open'],
        ['acme', 2, 'Kept', 'kept@example.test', 'keep'],
        ['acme', 3, 'Skipped', 'skip@example.test', 'skip-service'],
        ['beta', 1, 'VPN', 'bob@example.test', 'open'],
        ['beta', 2, 'Kept too', 'kept2@example.test', 'keep'],
        ['beta', 3, 'Laptop', 'eva@example.test', 'open'],
        ['gamma', 1, 'Mail', 'luis@example.test', 'open'],
    ];

    /** Ticket keys the run may change: not excluded by the entity pattern, not skipped by the service. */
    private const CHANGED_TICKETS = ['acme/1', 'beta/1', 'beta/3', 'gamma/1'];

    /** id => [class, name, email, status]: persons and companies interleaved in one table. */
    private const PARTIES = [
        1 => [EqPerson::class, 'Ana', 'ana@example.test', 'active'],
        2 => [EqCompany::class, 'Acme', 'info@acme.test', 'active'],
        3 => [EqPerson::class, 'Ivan', 'ivan@example.test', 'inactive'],
        4 => [EqPerson::class, 'Kim', 'kim@keep.test', 'active'],
        5 => [EqCompany::class, 'Beta', 'info@beta.test', 'active'],
        6 => [EqPerson::class, 'Sam', 'sam@example.test', 'skip-service'],
        7 => [EqPerson::class, 'Eva', 'eva@example.test', 'active'],
        8 => [EqPerson::class, 'Luis', 'luis@example.test', 'active'],
    ];

    /** Persons the run may change: included (active), not excluded (@keep.test); companies never. */
    private const CHANGED_PARTIES = ['1', '7', '8'];

    private string $tempDir;

    /** @var list<EntityManager> */
    private array $managers = [];

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/anonymize_equivalence_edge_' . uniqid();
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
     * @return iterable<string, array{bool, bool}>
     */
    public static function modes(): iterable
    {
        yield 'row by row, real run' => [false, false];
        yield 'by batch, real run' => [true, false];
        yield 'row by row, dry run' => [false, true];
        yield 'by batch, dry run' => [true, true];
    }

    #[DataProvider('modes')]
    #[IgnoreDeprecations]
    public function testCompositeKeyAndStiPathsWriteExactlyTheSameCells(bool $batch, bool $dryRun): void
    {
        $original = $this->snapshot($this->seededManager());

        $runs = [];
        foreach (['legacy' => LegacyPatientAnonymizer::class, 'row' => RowPatientAnonymizer::class, 'dual' => DualPatientAnonymizer::class] as $name => $class) {
            $anonymizer  = new $class(new CallLog(), $batch);
            $em          = $this->seededManager();
            $runs[$name] = $this->runCommand($em, $anonymizer, $dryRun) + ['snapshot' => $this->snapshot($em), 'log' => $anonymizer->log->calls];
        }

        $expected = $dryRun ? $original : $this->expected($original);
        self::assertSame(0, $runs['legacy']['exitCode'], $runs['legacy']['output']);
        self::assertSame($expected, $runs['legacy']['snapshot'], 'the deprecated path writes exactly the expected cells');
        foreach (['row', 'dual'] as $name) {
            self::assertSame(0, $runs[$name]['exitCode'], $runs[$name]['output']);
            self::assertSame($runs['legacy']['snapshot'], $runs[$name]['snapshot'], $name . ': same rows, columns and values');
            self::assertSame($runs['legacy']['log'], $runs[$name]['log'], $name . ': same chunks, records and returned updates');
        }

        foreach ($runs['legacy']['log'] as $call) {
            self::assertSame($dryRun, $call['dryRun']);
            $records = $call['method'] === 'rows' ? $call['input'] : [$call['input']];
            foreach ($records as $record) {
                if ($call['table'] === 'eq_party') {
                    self::assertSame('person', $record['kind'], 'only the selected STI child reaches the service');
                }
            }
        }
        // By batch, chunks of two: 7 tickets => 4 calls, 6 persons => 3 calls. Row by row, only the rows the patterns
        // keep reach the service: 5 tickets (not "keep") and 3 persons (active, not @keep.test).
        self::assertCount($batch ? 7 : 8, $runs['legacy']['log']);
    }

    /**
     * @param array<string, array<array-key, array<string, mixed>>> $original
     *
     * @return array<string, array<array-key, array<string, mixed>>>
     */
    private function expected(array $original): array
    {
        $expected = $original;
        foreach (self::CHANGED_TICKETS as $key) {
            foreach (['subject', 'requester'] as $column) {
                $expected['eq_ticket'][$key][$column] = sprintf('anon:eq_ticket:%s:%s', $column, $key);
            }
        }
        foreach (self::CHANGED_PARTIES as $id) {
            foreach (['name', 'email'] as $column) {
                $expected['eq_party'][$id][$column] = sprintf('anon:eq_party:%s:%s', $column, $id);
            }
        }

        return $expected;
    }

    /**
     * Every column of every row of both tables (booleans normalised to 0 / 1).
     *
     * @return array<string, array<array-key, array<string, mixed>>>
     */
    private function snapshot(EntityManager $em): array
    {
        $tables = [];
        foreach (['eq_ticket' => 'tenant, number', 'eq_party' => 'id'] as $table => $order) {
            foreach ($em->getConnection()->fetchAllAssociative(sprintf('SELECT * FROM %s ORDER BY %s', $table, $order)) as $row) {
                ksort($row);
                $row['anonymized'] = (int) $row['anonymized'];
                $key               = $table === 'eq_ticket' ? $row['tenant'] . '/' . $row['number'] : (string) $row['id'];

                $tables[$table][$key] = $row;
            }
        }

        return $tables;
    }

    private function seededManager(): EntityManager
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(new AttributeDriver([__DIR__ . '/Entity']));
        if (PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        } else {
            $config->setProxyDir($this->tempDir . '/proxies');
            $config->setProxyNamespace('AnonymizeEdgeEquivalenceProxies');
            $config->setAutoGenerateProxyClasses(true);
        }

        $em = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config), $config);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
        foreach (self::TICKETS as [$tenant, $number, $subject, $requester, $status]) {
            $em->persist(new EqTicket($tenant, $number, $subject, $requester, $status));
        }
        foreach (self::PARTIES as $id => [$class, $name, $email, $status]) {
            $em->persist(new $class($id, $name, $email, $status));
        }
        $em->flush();
        $em->clear();
        $this->managers[] = $em;

        return $em;
    }

    /**
     * @return array{exitCode: int, output: string}
     */
    private function runCommand(EntityManager $em, object $anonymizer, bool $dryRun): array
    {
        $entities = [
            EqTicket::class => [
                'exclude_patterns'  => ['status' => 'keep'],
                'anonymize_service' => 'test.anonymizer',
                'mark_anonymized'   => true,
            ],
            EqPerson::class => [
                'include_patterns'  => ['status' => 'active'],
                'exclude_patterns'  => ['email' => '%@keep.test'],
                'anonymize_service' => 'test.anonymizer',
                'mark_anonymized'   => true,
            ],
        ];

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->willReturn($em);

        $provider = new ConfigAnonymizeMetadataProvider($entities);
        $command  = new AnonymizeCommand(
            new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher(), null, new ServiceLocator(['test.anonymizer' => static fn (): object => $anonymizer]), $provider),
            new PreFlightCheckService(new FakerFactory('en_US'), $provider),
            new AnonymizationHistoryService($this->tempDir . '/history'),
            new EnvironmentProtectionService(new ParameterBag(['kernel.environment' => 'test', 'kernel.project_dir' => $this->tempDir]), []),
            $doctrine,
            new ParameterBag(['nowo_anonymize.stats_output_dir' => '%kernel.project_dir%/var/stats']),
            $this->tempDir,
        );

        $input = new ArrayInput($dryRun ? ['--dry-run' => true, '--batch-size' => '2'] : ['--force' => true, '--batch-size' => '2']);
        $input->setInteractive(false);
        $output   = new BufferedOutput();
        $exitCode = $command->run($input, $output);
        $em->clear();

        return ['exitCode' => $exitCode, 'output' => $output->fetch()];
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
