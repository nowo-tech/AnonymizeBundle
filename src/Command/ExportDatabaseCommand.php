<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Nowo\AnonymizeBundle\Enum\SymfonyService;
use Nowo\AnonymizeBundle\Service\DatabaseExportService;
use Nowo\AnonymizeBundle\Service\EnvironmentProtectionService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Command to export databases.
 *
 * Exports databases (MySQL, PostgreSQL, SQLite, MongoDB) with optional compression.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsCommand(
    name: 'nowo:anonymize:export-db',
    description: 'Export databases to files with optional compression'
)]
final class ExportDatabaseCommand extends Command
{
    /**
     * Creates a new ExportDatabaseCommand instance.
     *
     * @param ContainerInterface $container The service container
     */
    public function __construct(
        private ContainerInterface $container
    ) {
        parent::__construct();
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> command exports databases to files.

This command will:
  1. Export databases from configured Doctrine ORM connections (MySQL, PostgreSQL, SQLite)
  2. Export MongoDB databases (detected from MONGODB_URL environment variable)
  3. Apply optional compression (gzip, bzip2, zip)
  4. Automatically update .gitignore to exclude export directory
  5. Support all database types: MySQL, PostgreSQL, SQLite, and MongoDB

Examples:
  <info>php %command.full_name%</info>
  <info>php %command.full_name% --connection default</info>
  <info>php %command.full_name% --compression zip --output-dir /tmp/exports</info>
  <info>php %command.full_name% --connection mysql --connection postgres</info>
HELP
            )
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Specific connections to export (default: all)')
            ->addOption('output-dir', 'o', InputOption::VALUE_OPTIONAL, 'Output directory for exports')
            ->addOption('filename-pattern', null, InputOption::VALUE_OPTIONAL, 'Filename pattern for exports')
            ->addOption('compression', null, InputOption::VALUE_OPTIONAL, 'Compression format: none, gzip, bzip2, zip', 'gzip')
            ->addOption('no-gitignore', null, InputOption::VALUE_NONE, 'Skip updating .gitignore file');
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input The input
     * @param OutputInterface $output The output
     * @return int The exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Enhanced environment protection checks
        $parameterBag = $this->getParameterBag();
        $environmentProtection = new EnvironmentProtectionService($parameterBag);

        $protectionErrors = $environmentProtection->performChecks();
        if (!empty($protectionErrors)) {
            $io->error('Environment protection checks failed:');
            foreach ($protectionErrors as $error) {
                $io->writeln(sprintf('  - %s', $error));
            }
            $io->warning('This bundle is intended for development purposes only.');

            return Command::FAILURE;
        }

        if (!$environmentProtection->isSafeEnvironment()) {
            $io->error(sprintf(
                'This command can only be executed in "dev" or "test" environment. Current environment: "%s".',
                $environmentProtection->getEnvironment()
            ));

            return Command::FAILURE;
        }

        // Get configuration
        $outputDir = $input->getOption('output-dir');
        $filenamePattern = $input->getOption('filename-pattern');
        $compression = $input->getOption('compression');
        $noGitignore = $input->getOption('no-gitignore');
        $connections = $input->getOption('connection');

        // Get default values from configuration if not provided
        $parameterBag = $this->getParameterBag();
        
        if ($outputDir === null) {
            $outputDir = $parameterBag->has('nowo_anonymize.export.output_dir')
                ? $parameterBag->get('nowo_anonymize.export.output_dir')
                : '%kernel.project_dir%/var/exports';
        }

        if ($filenamePattern === null) {
            $filenamePattern = $parameterBag->has('nowo_anonymize.export.filename_pattern')
                ? $parameterBag->get('nowo_anonymize.export.filename_pattern')
                : '{connection}_{database}_{date}_{time}.{format}';
        }

        if ($compression === null) {
            $compression = $parameterBag->has('nowo_anonymize.export.compression')
                ? $parameterBag->get('nowo_anonymize.export.compression')
                : 'gzip';
        }

        $autoGitignore = !$noGitignore && (
            $parameterBag->has('nowo_anonymize.export.auto_gitignore')
                ? $parameterBag->get('nowo_anonymize.export.auto_gitignore')
                : true
        );

        // Resolve kernel.project_dir if present
        if (str_contains($outputDir, '%kernel.project_dir%')) {
            if ($this->container->has('kernel')) {
                $kernel = $this->container->get('kernel');
                $projectDir = $kernel->getProjectDir();
                $outputDir = str_replace('%kernel.project_dir%', $projectDir, $outputDir);
            }
        }

        // Validate compression format
        if (!in_array($compression, ['none', 'gzip', 'bzip2', 'zip'], true)) {
            $io->error(sprintf('Invalid compression format: %s. Allowed values: none, gzip, bzip2, zip', $compression));

            return Command::FAILURE;
        }

        // Get Doctrine registry
        $doctrine = $this->container->get(SymfonyService::DOCTRINE);
        $allManagers = $doctrine->getManagerNames();

        // Check for MongoDB connection from environment
        $mongodbUrl = $_ENV['MONGODB_URL'] ?? getenv('MONGODB_URL');
        $hasMongoRequested = !empty($connections) && in_array('mongodb', $connections, true);
        $shouldIncludeMongo = (empty($connections) && $mongodbUrl) || $hasMongoRequested;

        // Build list of managers to process
        $managersToProcess = empty($connections) ? array_keys($allManagers) : array_intersect(array_keys($allManagers), $connections);

        // Add MongoDB if it should be included but isn't in the managers list
        if ($shouldIncludeMongo && !in_array('mongodb', $managersToProcess, true)) {
            $managersToProcess[] = 'mongodb';
        }

        if (empty($managersToProcess)) {
            $io->error('No entity managers found to process.');

            return Command::FAILURE;
        }

        // Create export service
        $exportService = new DatabaseExportService(
            $this->container,
            $outputDir,
            $filenamePattern,
            $compression,
            $autoGitignore
        );

        $io->title('Database Export');
        $io->section('Configuration');
        $io->table(
            ['Setting', 'Value'],
            [
                ['Output Directory', $outputDir],
                ['Filename Pattern', $filenamePattern],
                ['Compression', $compression],
                ['Auto .gitignore', $autoGitignore ? 'Yes' : 'No'],
                ['Connections', implode(', ', $managersToProcess)],
            ]
        );

        $io->section('Exporting Databases');

        $successCount = 0;
        $failureCount = 0;
        $exportedFiles = [];

        foreach ($managersToProcess as $managerName) {
            try {
                // Handle MongoDB separately (not a Doctrine ORM EntityManager)
                if ($managerName === 'mongodb') {
                    $io->writeln(sprintf('Exporting <info>%s</info> (mongodb)...', $managerName));

                    // Try to get MongoDB connection info from environment
                    $mongodbUrl = $_ENV['MONGODB_URL'] ?? getenv('MONGODB_URL');

                    if ($mongodbUrl) {
                        // Parse MongoDB URL
                        $parsedUrl = parse_url(str_replace('mongodb://', 'http://', $mongodbUrl));
                        $host = $parsedUrl['host'] ?? 'localhost';
                        $port = $parsedUrl['port'] ?? 27017;
                        $databaseName = trim($parsedUrl['path'] ?? 'anonymize_demo', '/');
                        $databaseName = explode('?', $databaseName)[0]; // Remove query params

                        $exportedFile = $exportService->exportMongoDB($managerName, $databaseName, $host, $port);
                    } else {
                        // Try to get from Doctrine connection if available
                        try {
                            $em = $doctrine->getManager($managerName);
                            $connection = $em->getConnection();
                            $params = $connection->getParams();
                            $host = $params['host'] ?? 'localhost';
                            $port = $params['port'] ?? 27017;
                            $database = $connection->getDatabase();
                            $exportedFile = $exportService->exportMongoDB($managerName, $database, $host, $port);
                        } catch (\Exception $e) {
                            $io->writeln(sprintf('  ⚠️  MongoDB connection not found. Set MONGODB_URL environment variable or configure MongoDB in Doctrine.'));
                            $failureCount++;
                            continue;
                        }
                    }
                } else {
                    // Handle ORM connections (MySQL, PostgreSQL, SQLite)
                    $em = $doctrine->getManager($managerName);
                    $connection = $em->getConnection();
                    $driver = $connection->getDriver()->getName();
                    $database = $connection->getDatabase();

                    $io->writeln(sprintf('Exporting <info>%s</info> (%s)...', $managerName, $driver));
                    $exportedFile = $exportService->exportConnection($em, $managerName);
                }

                if ($exportedFile !== null && file_exists($exportedFile)) {
                    $fileSize = filesize($exportedFile);
                    $fileSizeFormatted = $this->formatBytes($fileSize);
                    $io->writeln(sprintf('  ✓ Exported to: <info>%s</info> (%s)', $exportedFile, $fileSizeFormatted));
                    $exportedFiles[] = $exportedFile;
                    $successCount++;
                } else {
                    $io->writeln(sprintf('  ✗ Failed to export %s', $managerName));
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $io->writeln(sprintf('  ✗ Error exporting %s: %s', $managerName, $e->getMessage()));
                $failureCount++;
            }
        }

        $io->newLine();
        $io->section('Summary');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Connections', count($managersToProcess)],
                ['Successful', $successCount],
                ['Failed', $failureCount],
            ]
        );

        if ($autoGitignore) {
            $io->note('.gitignore has been updated to exclude the export directory.');
        }

        if ($successCount > 0) {
            $io->success(sprintf('Successfully exported %d database(s)!', $successCount));
        }

        if ($failureCount > 0) {
            $io->warning(sprintf('%d export(s) failed. Check the output above for details.', $failureCount));
        }

        return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Gets the parameter bag.
     *
     * @return ParameterBagInterface The parameter bag
     */
    private function getParameterBag(): ParameterBagInterface
    {
        // Try to get parameter_bag service
        if ($this->container->has('parameter_bag')) {
            try {
                return $this->container->get('parameter_bag');
            } catch (\Exception $e) {
                // parameter_bag not available
            }
        }

        // Fallback: create a wrapper that accesses parameters via kernel
        $container = $this->container;
        return new class ($container) implements ParameterBagInterface {
            public function __construct(private ContainerInterface $container) {}
            public function get(string $name): array|bool|string|int|float|\UnitEnum|null
            {
                if ($this->container->has('kernel')) {
                    $kernel = $this->container->get('kernel');
                    $reflection = new \ReflectionClass($kernel);
                    if ($reflection->hasProperty('container')) {
                        $property = $reflection->getProperty('container');
                        $property->setAccessible(true);
                        $kernelContainer = $property->getValue($kernel);
                        if ($kernelContainer instanceof \Symfony\Component\DependencyInjection\Container) {
                            if (method_exists($kernelContainer, 'getParameterBag')) {
                                $paramBag = $kernelContainer->getParameterBag();
                                if ($paramBag instanceof ParameterBagInterface) {
                                    return $paramBag->get($name);
                                }
                            }
                            if (method_exists($kernelContainer, 'getParameter')) {
                                return $kernelContainer->getParameter($name);
                            }
                        }
                    }
                }
                throw new \InvalidArgumentException(sprintf('Parameter "%s" not found', $name));
            }
            public function has(string $name): bool
            {
                try {
                    $this->get($name);
                    return true;
                } catch (\Exception $e) {
                    return false;
                }
            }
            public function set(string $name, array|bool|string|int|float|\UnitEnum|null $value): void {}
            public function remove(string $name): void {}
            public function all(): array
            {
                return [];
            }
            public function replace(array $parameters): void {}
            public function add(array $parameters): void {}
            public function clear(): void {}
            public function resolve(): void {}
            public function resolveValue(mixed $value): mixed
            {
                return $value;
            }
            public function escapeValue(mixed $value): mixed
            {
                return $value;
            }
            public function unescapeValue(mixed $value): mixed
            {
                return $value;
            }
        };
    }

    /**
     * Formats bytes to human-readable format.
     *
     * @param int $bytes The number of bytes
     * @return string The formatted string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
