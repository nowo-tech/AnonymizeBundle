<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Kernel;

use Composer\InstalledVersions;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Faker\ShuffleFaker;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Tests\Integration\Ddd\Domain\Patient;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;

use function array_keys;
use function gc_collect_cycles;
use function ksort;
use function memory_get_usage;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function uniqid;
use function version_compare;

use const E_USER_DEPRECATED;

/**
 * 1.6.1, REQ-RUNTIME-002: one kept-alive kernel, the services resetter never runs (FrankenPHP worker mode with kernel
 * reset false and no `services_resetter`, or a scheduler running commands in-process), and `nowo:anonymize:run` runs
 * N times over two entity sets in alternation.
 *
 * Every run must anonymize exactly the rows of its own set (nothing of the previous set survives), the seeded shuffle
 * must give the same value on every run, the faker cache must be empty after every outermost command
 * (`console.terminate`, not `kernel.reset`), and memory must stay flat.
 */
final class WorkerKeptAliveKernelTest extends TestCase
{
    /** Runs of the command; each one rewrites the table and anonymizes it, so N is moderate. */
    private const RUNS = 120;

    /** Runs before the memory baseline is taken. */
    private const WARM_UP = 20;

    /** Allowed memory growth after the warm-up. */
    private const MEMORY_MARGIN_BYTES = 2 * 1024 * 1024;

    private const CITIES = ['Lugo', 'Soria', 'Teruel', 'Cuenca', 'Zamora'];

    private const SEED = 42;

    /**
     * Two entity sets: id => [email, full name, status, phone, city].
     *
     * @var array<string, array<string, array{string, string, string, string|null, string}>>
     */
    private const SETS = [
        'alpha' => [
            'a-1' => ['ana@alpha.test', 'Ana Alpha', 'active', '+34 600 000 001', 'Madrid'],
            'a-2' => ['kept@alpha.test', 'Kept Alpha', 'keep', '+34 600 000 002', 'Bilbao'],
            'a-3' => ['eva@alpha.test', 'Eva Alpha', 'active', null, 'Vigo'],
        ],
        'beta' => [
            'b-1' => ['bob@beta.test', 'Bob Beta', 'active', '+44 7700 900001', 'Leeds'],
            'b-2' => ['cy@beta.test', 'Cy Beta', 'active', '+44 7700 900002', 'York'],
            'b-3' => ['kept@beta.test', 'Kept Beta', 'keep', null, 'Bath'],
            'b-4' => ['di@beta.test', 'Di Beta', 'active', '+44 7700 900004', 'Hull'],
        ],
    ];

    private string $baseDir;

    private ?AnonymizeTestKernel $kernel = null;

    protected function setUp(): void
    {
        // Same guard as RowAnonymizerPortsKernelTest: a mixed symfony/yaml 6.4 + symfony/console 8 install cannot boot
        // FrameworkBundle (not this bundle's code).
        $yaml    = InstalledVersions::getVersion('symfony/yaml');
        $console = InstalledVersions::getVersion('symfony/console');
        if ($yaml !== null && $console !== null && version_compare($yaml, '7.0', '<') && version_compare($console, '8.0', '>=')) {
            self::markTestSkipped(sprintf('symfony/yaml %s cannot be loaded with symfony/console %s.', $yaml, $console));
        }
        $this->baseDir = sys_get_temp_dir() . '/nowo-anonymize-worker-' . uniqid();
    }

    protected function tearDown(): void
    {
        $this->kernel?->shutdown();
        $this->kernel = null;
        (new Filesystem())->remove($this->baseDir);
    }

    /**
     * Deprecations of mixed Symfony component versions in the CI matrix (console `add()`, event-dispatcher hot-path
     * events) are not this bundle's and are dropped; the bundle itself must trigger none (same policy as
     * RowAnonymizerPortsKernelTest).
     */
    public function testRunsInOneKernelWithoutResetterAnonymizeOnlyTheirOwnSetAndKeepMemoryFlat(): void
    {
        $deprecations = [];
        // @phpstan-ignore frankenphp.worker.noSetErrorExceptionHandler (test only; restored in finally)
        set_error_handler(static function (int $type, string $message) use (&$deprecations): bool {
            if (str_contains($message, 'nowo-tech/anonymize-bundle')) {
                $deprecations[] = $message;
            }

            return true;
        }, E_USER_DEPRECATED);
        try {
            $this->runCommandsInOneKernel();
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $deprecations);
    }

    private function runCommandsInOneKernel(): void
    {
        $this->kernel = new AnonymizeTestKernel($this->baseDir, static function (ContainerConfigurator $container): void {
            $container->extension('nowo_anonymize', [
                'entities' => [Patient::class => [
                    'exclude_patterns' => ['status' => 'keep'],
                    'mark_anonymized'  => true,
                    'properties'       => [
                        'email'         => ['type' => 'email', 'weight' => 1],
                        'fullName'      => ['type' => 'constant', 'options' => ['value' => 'Anonymous patient']],
                        'contact.phone' => ['type' => 'constant', 'options' => ['value' => '000 000 000']],
                        'contact.city'  => ['type' => 'shuffle', 'options' => ['values' => self::CITIES, 'seed' => self::SEED]],
                    ],
                ]],
            ]);
        }, false);
        $this->kernel->boot();
        $container = $this->kernel->getContainer();

        $registry = $container->get(ManagerRegistry::class);
        self::assertInstanceOf(ManagerRegistry::class, $registry);
        $em = $registry->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $em);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
        $connection = $em->getConnection();

        $service = $container->get(AnonymizeService::class);
        self::assertInstanceOf(AnonymizeService::class, $service);
        $fakerCache   = new ReflectionProperty(AnonymizeService::class, 'fakerCache');
        $expectedCity = (new ShuffleFaker())->generate(['values' => self::CITIES, 'seed' => self::SEED]);

        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $baseline = 0;
        for ($run = 0; $run < self::RUNS; ++$run) {
            if ($run === self::WARM_UP) {
                gc_collect_cycles();
                $baseline = memory_get_usage();
            }

            $set = $run % 2 === 0 ? 'alpha' : 'beta';
            $this->seed($connection, $set);

            $input = new ArrayInput(['command' => 'nowo:anonymize:run', '--force' => true]);
            $input->setInteractive(false);
            $output = new BufferedOutput();
            self::assertSame(0, $application->run($input, $output), $output->fetch());

            $this->assertOnlyThisSetAnonymized($connection, $set, $expectedCity, $run);
            self::assertSame([], $fakerCache->getValue($service), sprintf('run %d: faker cache cleared at console.terminate', $run));
        }

        gc_collect_cycles();
        $growth = memory_get_usage() - $baseline;
        self::assertLessThan(self::MEMORY_MARGIN_BYTES, $growth, sprintf('memory grew by %d bytes over %d runs', $growth, self::RUNS - self::WARM_UP));
    }

    /**
     * Replaces the table with the rows of one set (DBAL only: nothing enters the host's identity map).
     */
    private function seed(Connection $connection, string $set): void
    {
        $connection->executeStatement('DELETE FROM ddd_patient');
        foreach (self::SETS[$set] as $id => [$email, $name, $status, $phone, $city]) {
            $connection->insert('ddd_patient', [
                'id'            => $id,
                'email'         => $email,
                'full_name'     => $name,
                'status'        => $status,
                'anonymized'    => 0,
                'contact_phone' => $phone,
                'contact_city'  => $city,
            ]);
        }
    }

    /**
     * @return array<string, array{string, string, string, string|null, string}>
     */
    private function rowsOf(string $set): array
    {
        return self::SETS[$set];
    }

    private function assertOnlyThisSetAnonymized(Connection $connection, string $set, mixed $expectedCity, int $run): void
    {
        $rows = [];
        foreach ($connection->fetchAllAssociative('SELECT * FROM ddd_patient ORDER BY id') as $row) {
            $rows[(string) $row['id']] = $row;
        }
        $expectedIds = array_keys(self::SETS[$set]);
        ksort($rows);
        self::assertSame($expectedIds, array_keys($rows), sprintf('run %d', $run));

        foreach ($this->rowsOf($set) as $id => [$email, $name, $status, $phone, $city]) {
            $row     = $rows[$id];
            $message = sprintf('run %d, %s', $run, $id);
            if ($status === 'keep') {
                self::assertSame($email, $row['email'], $message);
                self::assertSame($name, $row['full_name'], $message);
                self::assertSame($phone, $row['contact_phone'], $message);
                self::assertSame($city, $row['contact_city'], $message);
                self::assertSame(0, (int) $row['anonymized'], $message);
                continue;
            }

            self::assertNotSame($email, $row['email'], $message);
            self::assertTrue(str_contains((string) $row['email'], '@'), $message);
            foreach (self::SETS as $otherSet => $rowsOfSet) {
                foreach ($rowsOfSet as $otherId => [$otherEmail]) {
                    self::assertNotSame($otherEmail, $row['email'], sprintf('%s: no e-mail of %s/%s', $message, $otherSet, $otherId));
                }
            }
            self::assertSame('Anonymous patient', $row['full_name'], $message);
            // A NULL cell is left NULL (the bundle does not fake missing values).
            self::assertSame($phone === null ? null : '000 000 000', $row['contact_phone'], $message);
            self::assertSame($expectedCity, $row['contact_city'], $message . ': seeded shuffle is the same on every run');
            self::assertSame(1, (int) $row['anonymized'], $message);
        }
    }
}
