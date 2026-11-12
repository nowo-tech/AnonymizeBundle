<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Kernel;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer\CallLog;
use Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer\DualPatientAnonymizer;
use Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer\LegacyPatientAnonymizer;
use Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer\PatientRedaction;
use Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer\RowPatientAnonymizer;
use Nowo\AnonymizeBundle\Tests\Integration\Ddd\Domain\ContactDetails;
use Nowo\AnonymizeBundle\Tests\Integration\Ddd\Domain\Patient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;

use function count;
use function sprintf;
use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;

use const E_USER_DEPRECATED;

/**
 * 1.6, DDD host kernel: an XML-mapped plain-PHP domain selected through `nowo_anonymize.entities`, the host's own
 * ManagerRegistry (no DoctrineBundle), and the entity anonymizer service on the new row SPI only, on the deprecated
 * SPI only, or on both. In every case `lint:container` passes, the service reaches the closed plugin locator, and
 * `nowo:anonymize:run` writes exactly the same cells.
 */
final class RowAnonymizerPortsKernelTest extends TestCase
{
    private string $baseDir;

    /** @var list<AnonymizeTestKernel> */
    private array $kernels = [];

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/nowo-anonymize-kernel-' . uniqid();
    }

    protected function tearDown(): void
    {
        foreach ($this->kernels as $kernel) {
            $kernel->shutdown();
        }
        $this->kernels = [];
        (new Filesystem())->remove($this->baseDir);
    }

    /**
     * @return iterable<string, array{class-string, bool}>
     */
    public static function newPorts(): iterable
    {
        foreach ([0 => 'row by row', 1 => 'by batch'] as $batch => $mode) {
            yield 'new port only, ' . $mode => [RowPatientAnonymizer::class, $batch === 1];
            yield 'both ports, ' . $mode => [DualPatientAnonymizer::class, $batch === 1];
        }
    }

    /**
     * A host on the new port boots, lints and runs without any deprecation from this bundle (deprecations of mixed
     * Symfony component versions in the CI matrix are not this bundle's).
     *
     * @param class-string $anonymizerClass
     */
    #[DataProvider('newPorts')]
    public function testNewPortLintsAndRunsWithoutDeprecations(string $anonymizerClass, bool $batch): void
    {
        self::assertSame([], $this->bundleDeprecations(fn () => $this->assertLintAndRun($anonymizerClass, $batch)));
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
    public function testDeprecatedPortOnlyLintsAndRunsTheSame(bool $batch): void
    {
        $deprecations = $this->bundleDeprecations(fn () => $this->assertLintAndRun(LegacyPatientAnonymizer::class, $batch));

        self::assertNotSame([], $deprecations);
        foreach ($deprecations as $deprecation) {
            self::assertStringContainsString('The anonymizer service "app.patient_anonymizer" implements only', $deprecation);
        }
    }

    /**
     * Runs $test and returns the deprecations this bundle triggered meanwhile (others are dropped).
     *
     * @param callable(): void $test
     *
     * @return list<string>
     */
    private function bundleDeprecations(callable $test): array
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
            $test();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }

    /**
     * @param class-string $anonymizerClass
     */
    private function assertLintAndRun(string $anonymizerClass, bool $batch): void
    {
        $kernel      = $this->boot($anonymizerClass, $batch);
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $lint = new CommandTester($application->find('lint:container'));
        self::assertSame(0, $lint->execute([]), $lint->getDisplay());
        self::assertStringContainsString('linted successfully', $lint->getDisplay());

        $em = $this->entityManager($kernel);
        $this->seed($em);
        $original = $this->snapshot($em);

        $dryRun = new CommandTester($application->find('nowo:anonymize:run'));
        self::assertSame(0, $dryRun->execute(['--dry-run' => true, '--batch-size' => '2'], ['interactive' => false]), $dryRun->getDisplay());
        self::assertSame($original, $this->snapshot($em), 'a dry run changes no cell');

        $run = new CommandTester($application->find('nowo:anonymize:run'));
        self::assertSame(0, $run->execute(['--force' => true, '--batch-size' => '2'], ['interactive' => false]), $run->getDisplay());

        $expected = $original;
        foreach (['p-1', 'p-4'] as $id) {
            foreach (PatientRedaction::COLUMNS as $column) {
                $expected[$id][$column] = sprintf('anon:ddd_patient:%s:%s', $column, $id);
            }
        }
        self::assertSame($expected, $this->snapshot($em), 'exactly the cells of the deprecated path, nothing else');
    }

    /**
     * @param class-string $anonymizerClass
     */
    private function boot(string $anonymizerClass, bool $batch): AnonymizeTestKernel
    {
        $baseDir = $this->baseDir . '/' . count($this->kernels);
        $kernel  = new AnonymizeTestKernel($baseDir, static function (ContainerConfigurator $container) use ($anonymizerClass, $batch): void {
            $container->extension('nowo_anonymize', [
                'entities' => [Patient::class => [
                    'exclude_patterns'  => ['status' => 'keep'],
                    'anonymize_service' => 'app.patient_anonymizer',
                ]],
            ]);
            $container->services()
                ->set('app.patient_anonymizer', $anonymizerClass)
                ->args([inline_service(CallLog::class), $batch]);
        });
        $kernel->boot();
        $this->kernels[] = $kernel;

        return $kernel;
    }

    private function entityManager(AnonymizeTestKernel $kernel): EntityManagerInterface
    {
        $registry = $kernel->getContainer()->get(ManagerRegistry::class);
        self::assertInstanceOf(ManagerRegistry::class, $registry);
        $em = $registry->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $em);

        return $em;
    }

    private function seed(EntityManagerInterface $em): void
    {
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
        $em->persist(new Patient('p-1', 'ana@example.test', 'Ana Real', 'active', new ContactDetails('+34 600 000 001', 'Madrid')));
        $em->persist(new Patient('p-2', 'kept@example.test', 'Kept Real', 'keep', new ContactDetails('+34 600 000 002', 'Bilbao')));
        $em->persist(new Patient('p-3', 'skip@example.test', 'Skip Real', 'skip-service', new ContactDetails(null, null)));
        $em->persist(new Patient('p-4', 'eva@example.test', 'Eva Real', 'active', new ContactDetails(null, 'Vigo')));
        $em->flush();
        $em->clear();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function snapshot(EntityManagerInterface $em): array
    {
        $rows = [];
        foreach ($em->getConnection()->fetchAllAssociative('SELECT * FROM ddd_patient ORDER BY id') as $row) {
            ksort($row);
            $row['anonymized']         = (int) $row['anonymized'];
            $rows[(string) $row['id']] = $row;
        }

        return $rows;
    }
}
