<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Nowo\AnonymizeBundle\Enum\SymfonyService;
use Nowo\AnonymizeBundle\Event\AfterAnonymizeEvent;
use Nowo\AnonymizeBundle\Event\BeforeAnonymizeEvent;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\AnonymizeStatistics;
use Nowo\AnonymizeBundle\Service\EnvironmentProtectionService;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
use Nowo\AnonymizeBundle\Service\PreFlightCheckService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Container\ContainerInterface;

/**
 * Command to anonymize database records.
 *
 * This command processes all entities marked with the Anonymize attribute
 * across all Doctrine connections and anonymizes properties marked with
 * the AnonymizeProperty attribute.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsCommand(
    name: 'nowo:anonymize:run',
    description: 'Anonymize database records using Doctrine attributes'
)]
final class AnonymizeCommand extends Command
{
    private const PREFIX_COMMAND = 'nowo:anonymize:';

    /**
     * Creates a new AnonymizeCommand instance.
     *
     * @param ContainerInterface $container The service container
     * @param string $locale The default locale for Faker generator (default: 'en_US')
     * @param array<string> $connections The default connections to process (empty = all)
     * @param bool $dryRun The default dry-run mode (default: false)
     * @param int $batchSize The default batch size for processing records (default: 100)
     */
    public function __construct(
        private ContainerInterface $container,
        private string $locale = 'en_US',
        private array $connections = [],
        private bool $dryRun = false,
        private int $batchSize = 100
    ) {
        parent::__construct();
    }

    /**
     * Configures the command options.
     */
    protected function configure(): void
    {
        $this
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> command anonymizes database records based on Doctrine attributes.

  <info>php %command.full_name%</info>

This command will:
  1. Scan all Doctrine ORM connections for entities with the #[Anonymize] attribute
  2. Process properties marked with #[AnonymizeProperty] attribute
  3. Anonymize values using Faker generators
  4. Respect weight ordering (lower weights first, then alphabetical)
  5. Apply inclusion/exclusion patterns

Note: Currently supports Doctrine ORM (MySQL, PostgreSQL, SQLite).
      MongoDB ODM support is planned for future releases.

Options:
  --connection, -c    Process only specific connections (can be used multiple times)
  --dry-run          Show what would be anonymized without making changes
  --batch-size, -b   Number of records to process in each batch (default: 100)
  --locale, -l       Locale for Faker generator (default: en_US)
  --stats-json       Export statistics to JSON file (relative paths use configured stats_output_dir)
  --stats-csv         Export statistics to CSV file (relative paths use configured stats_output_dir)
  --stats-only       Show only statistics summary (suppress detailed output)
  --no-progress      Disable progress bar display
  --verbose, -v      Increase verbosity of messages (Symfony standard option)
  --debug            Enable debug mode (shows detailed information)
  --interactive, -i  Enable interactive mode with step-by-step confirmations

Examples:
  <info>php %command.full_name%</info>
  <info>php %command.full_name% --dry-run</info>
  <info>php %command.full_name% --connection default --connection secondary</info>
  <info>php %command.full_name% --batch-size 50 --locale en_US</info>
  <info>php %command.full_name% --stats-json stats.json</info>
  <info>php %command.full_name% --stats-csv stats.csv</info>
  <info>php %command.full_name% --stats-only</info>
  <info>php %command.full_name% --verbose</info>
  <info>php %command.full_name% --debug</info>
  <info>php %command.full_name% --interactive</info>
HELP
            )
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Specific connections to process (default: all)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be anonymized without making changes')
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, 'Batch size for processing records', $this->batchSize)
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Locale for Faker generator', $this->locale)
            ->addOption('stats-json', null, InputOption::VALUE_OPTIONAL, 'Export statistics to JSON file')
            ->addOption('stats-csv', null, InputOption::VALUE_OPTIONAL, 'Export statistics to CSV file')
            ->addOption('stats-only', null, InputOption::VALUE_NONE, 'Show only statistics summary')
            ->addOption('no-progress', null, InputOption::VALUE_NONE, 'Disable progress bar display')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Enable debug mode (shows detailed information)')
            ->addOption('interactive', 'i', InputOption::VALUE_NONE, 'Enable interactive mode with step-by-step confirmations');
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
        // Get parameter bag - try different ways depending on Symfony version
        $parameterBag = null;
        
        // Try to get parameter_bag service
        if ($this->container->has('parameter_bag')) {
            try {
                $parameterBag = $this->container->get('parameter_bag');
            } catch (\Exception $e) {
                // parameter_bag not available
            }
        }
        
        // Fallback: create a wrapper that accesses parameters via kernel
        if ($parameterBag === null) {
            $container = $this->container;
            // Create a simple parameter bag wrapper
            $parameterBag = new class($container) implements ParameterBagInterface {
                public function __construct(private ContainerInterface $container) {}
                public function get(string $name): array|bool|string|int|float|\UnitEnum|null { 
                    // Access kernel container's parameter bag via reflection
                    if ($this->container->has('kernel')) {
                        $kernel = $this->container->get('kernel');
                        $reflection = new \ReflectionClass($kernel);
                        if ($reflection->hasProperty('container')) {
                            $property = $reflection->getProperty('container');
                            $property->setAccessible(true);
                            $kernelContainer = $property->getValue($kernel);
                            if ($kernelContainer instanceof \Symfony\Component\DependencyInjection\Container) {
                                // Try getParameterBag method first
                                if (method_exists($kernelContainer, 'getParameterBag')) {
                                    $paramBag = $kernelContainer->getParameterBag();
                                    if ($paramBag instanceof ParameterBagInterface) {
                                        return $paramBag->get($name);
                                    }
                                }
                                // Fallback: access parameterBag property via reflection
                                $paramReflection = new \ReflectionClass($kernelContainer);
                                if ($paramReflection->hasProperty('parameterBag')) {
                                    $paramProperty = $paramReflection->getProperty('parameterBag');
                                    $paramProperty->setAccessible(true);
                                    $paramBag = $paramProperty->getValue($kernelContainer);
                                    if ($paramBag instanceof ParameterBagInterface) {
                                        return $paramBag->get($name);
                                    }
                                }
                                // Last resort: try getParameter method directly
                                if (method_exists($kernelContainer, 'getParameter')) {
                                    return $kernelContainer->getParameter($name);
                                }
                            }
                        }
                    }
                    throw new \InvalidArgumentException(sprintf('Parameter "%s" not found', $name));
                }
                public function has(string $name): bool { 
                    try {
                        $this->get($name);
                        return true;
                    } catch (\Exception $e) {
                        return false;
                    }
                }
                public function set(string $name, array|bool|string|int|float|\UnitEnum|null $value): void {}
                public function remove(string $name): void {}
                public function all(): array { return []; }
                public function replace(array $parameters): void {}
                public function add(array $parameters): void {}
                public function clear(): void {}
                public function resolve(): void {}
                public function resolveValue(mixed $value): mixed { return $value; }
                public function escapeValue(mixed $value): mixed { return $value; }
                public function unescapeValue(mixed $value): mixed { return $value; }
            };
        }
        
        $environmentProtection = new EnvironmentProtectionService($parameterBag);

        $protectionErrors = $environmentProtection->performChecks();
        if (!empty($protectionErrors)) {
            $io->error('Environment protection checks failed:');
            foreach ($protectionErrors as $error) {
                $io->writeln(sprintf('  - %s', $error));
            }
            $io->warning('This bundle is intended for development purposes only and should not be used in production.');
            $io->note('Please review your configuration and ensure the bundle is only enabled for "dev" and "test" environments.');

            return Command::FAILURE;
        }

        // Additional check for environment
        if (!$environmentProtection->isSafeEnvironment()) {
            $io->error(sprintf(
                'This command can only be executed in "dev" or "test" environment. Current environment: "%s".',
                $environmentProtection->getEnvironment()
            ));
            $io->warning('This bundle is intended for development purposes only and should not be used in production.');

            return Command::FAILURE;
        }

        // Get options
        $connections = $input->getOption('connection') ?: $this->connections;
        $dryRun = $input->getOption('dry-run') || $this->dryRun;
        $batchSize = (int) ($input->getOption('batch-size') ?: $this->batchSize);
        $locale = $input->getOption('locale') ?: $this->locale;
        $verbose = $input->getOption('verbose') || $output->isVerbose();
        $debug = $input->getOption('debug') || $output->isDebug();
        $interactive = $input->getOption('interactive');

        if ($dryRun) {
            $io->warning('DRY RUN MODE: No changes will be made to the database');
        }

        if ($debug) {
            $io->note('DEBUG MODE: Detailed information will be displayed');
        }

        if ($verbose) {
            $io->note('VERBOSE MODE: Additional information will be displayed');
        }

        // Get Doctrine registry
        $doctrine = $this->container->get(SymfonyService::DOCTRINE);

        // Get all entity manager names (not connection names)
        $allManagers = $doctrine->getManagerNames();
        $managersToProcess = empty($connections) ? array_keys($allManagers) : array_intersect(array_keys($allManagers), $connections);

        // Check if MongoDB connection is requested but not supported yet
        $requestedConnections = empty($connections) ? [] : $connections;
        if (!empty($requestedConnections) && in_array('mongodb', $requestedConnections, true)) {
            $io->warning('MongoDB ODM support is not yet available. The command currently only supports Doctrine ORM (MySQL, PostgreSQL, SQLite).');
            $io->note('MongoDB ODM support is planned for future releases. For now, you can use Mongo Express to view MongoDB data.');
        }

        if (empty($managersToProcess)) {
            $io->error('No entity managers found to process.');

            return Command::FAILURE;
        }

        // Initialize services
        $fakerFactory = new FakerFactory($locale, $this->container);
        $patternMatcher = new PatternMatcher();
        $eventDispatcher = $this->container->has('event_dispatcher') ? $this->container->get('event_dispatcher') : null;
        $anonymizeService = new AnonymizeService($fakerFactory, $patternMatcher, $eventDispatcher);
        $preFlightCheck = new PreFlightCheckService($fakerFactory);
        $statistics = new AnonymizeStatistics();
        $statistics->start();

        $statsOnly = $input->getOption('stats-only');
        $statsJson = $input->getOption('stats-json');
        $statsCsv = $input->getOption('stats-csv');
        
        // Get stats output directory from configuration
        $statsOutputDir = $this->getParameter('nowo_anonymize.stats_output_dir', '%kernel.project_dir%/var/stats');
        
        // Resolve kernel.project_dir if present
        if (str_contains($statsOutputDir, '%kernel.project_dir%')) {
            if ($this->container->has('kernel')) {
                $kernel = $this->container->get('kernel');
                $projectDir = $kernel->getProjectDir();
                $statsOutputDir = str_replace('%kernel.project_dir%', $projectDir, $statsOutputDir);
            }
        }
        
        // Process stats file paths - if relative, use configured output directory
        if ($statsJson !== null && !str_starts_with($statsJson, '/') && !str_contains($statsJson, '\\')) {
            // Relative path - prepend output directory
            if (!is_dir($statsOutputDir)) {
                mkdir($statsOutputDir, 0755, true);
            }
            $statsJson = rtrim($statsOutputDir, '/') . '/' . $statsJson;
        }
        
        if ($statsCsv !== null && !str_starts_with($statsCsv, '/') && !str_contains($statsCsv, '\\')) {
            // Relative path - prepend output directory
            if (!is_dir($statsOutputDir)) {
                mkdir($statsOutputDir, 0755, true);
            }
            $statsCsv = rtrim($statsOutputDir, '/') . '/' . $statsCsv;
        }

        // Show summary if interactive mode
        if ($interactive && !$statsOnly) {
            $io->title('Interactive Mode - Anonymization Summary');
            $io->writeln(sprintf('Entity managers to process: <info>%s</info>', implode(', ', $managersToProcess)));
            $io->writeln(sprintf('Batch size: <info>%d</info>', $batchSize));
            $io->writeln(sprintf('Locale: <info>%s</info>', $locale));
            if ($dryRun) {
                $io->writeln('<comment>DRY RUN MODE: No changes will be made</comment>');
            }
            $io->newLine();

            if (!$io->confirm('Do you want to proceed with anonymization?', false)) {
                $io->warning('Anonymization cancelled by user.');

                return Command::SUCCESS;
            }
            $io->newLine();
        }

        // Process each entity manager
        foreach ($managersToProcess as $managerName) {
            if (!$statsOnly) {
                $io->section(sprintf('Processing entity manager: %s', $managerName));
            }

            try {
                $em = $doctrine->getManager($managerName);

                // Perform pre-flight checks
                $entities = $anonymizeService->getAnonymizableEntities($em);
                if (!empty($entities)) {
                    if ($debug) {
                        $io->writeln(sprintf('<comment>[DEBUG]</comment> Performing pre-flight checks for %d entity(ies)...', count($entities)));
                    }

                    $preFlightErrors = $preFlightCheck->performChecks($em, $entities);
                    if (!empty($preFlightErrors)) {
                        $io->error('Pre-flight checks failed:');
                        foreach ($preFlightErrors as $error) {
                            $io->writeln(sprintf('  - %s', $error));
                        }
                        $io->warning('Please fix the errors above before running anonymization.');

                        return Command::FAILURE;
                    }

                    if (!$statsOnly) {
                        $io->success('Pre-flight checks passed');
                        if ($debug) {
                            $io->writeln('<comment>[DEBUG]</comment> All pre-flight checks completed successfully');
                        }
                    }

                    // Interactive confirmation for entity manager
                    if ($interactive && !$statsOnly) {
                        $io->writeln(sprintf('Found <info>%d</info> entity(ies) to process in <info>%s</info>:', count($entities), $managerName));
                        foreach ($entities as $entityClass) {
                            $io->writeln(sprintf('  - <info>%s</info>', $entityClass));
                        }
                        $io->newLine();

                        if (!$io->confirm(sprintf('Do you want to process entity manager <info>%s</info>?', $managerName), true)) {
                            $io->note(sprintf('Skipping entity manager: %s', $managerName));
                            continue;
                        }
                        $io->newLine();
                    }
                }

                $this->processConnection($io, $em, $anonymizeService, $batchSize, $dryRun, $managerName, $statistics, $statsOnly, $input, $output, $verbose, $debug, $interactive);
            } catch (\Exception $e) {
                $io->error(sprintf('Error processing entity manager %s: %s', $managerName, $e->getMessage()));

                return Command::FAILURE;
            }
        }

        $statistics->stop();

        // Save to history
        try {
            $historyDir = $this->getParameter('nowo_anonymize.history_dir', '%kernel.project_dir%/var/anonymize_history');
            if (str_contains($historyDir, '%kernel.project_dir%')) {
                if ($this->container->has('kernel')) {
                    $kernel = $this->container->get('kernel');
                    $projectDir = $kernel->getProjectDir();
                    $historyDir = str_replace('%kernel.project_dir%', $projectDir, $historyDir);
                }
            }
            
            $historyService = new \Nowo\AnonymizeBundle\Service\AnonymizationHistoryService($historyDir);
            $metadata = [
                'command' => 'nowo:anonymize:run',
                'connections' => $connections,
                'batch_size' => $batchSize,
                'locale' => $locale,
                'dry_run' => $dryRun,
                'interactive' => $interactive,
            ];
            $historyService->saveRun($statistics->getAll(), $metadata);
        } catch (\Exception $e) {
            // Silently fail if history cannot be saved
            if ($debug) {
                $io->writeln(sprintf('<comment>[DEBUG]</comment> Failed to save history: %s', $e->getMessage()));
            }
        }

        // Display statistics
        $this->displayStatistics($io, $statistics, $statsOnly, $statsJson, $statsCsv);

        return Command::SUCCESS;
    }

    /**
     * Processes a single Doctrine entity manager.
     *
     * @param SymfonyStyle $io The I/O helper
     * @param EntityManagerInterface $em The entity manager
     * @param AnonymizeService $anonymizeService The anonymize service
     * @param int $batchSize The batch size
     * @param bool $dryRun If true, only show what would be anonymized
     * @param string $managerName The entity manager name
     * @param AnonymizeStatistics $statistics The statistics collector
     * @param bool $statsOnly If true, only collect statistics without output
     */
    private function processConnection(
        SymfonyStyle $io,
        EntityManagerInterface $em,
        AnonymizeService $anonymizeService,
        int $batchSize,
        bool $dryRun,
        string $managerName,
        AnonymizeStatistics $statistics,
        bool $statsOnly = false,
        ?InputInterface $input = null,
        ?OutputInterface $output = null,
        bool $verbose = false,
        bool $debug = false,
        bool $interactive = false
    ): void {

        // Get all anonymizable entities
        $entities = $anonymizeService->getAnonymizableEntities($em);

        if (empty($entities)) {
            if (!$statsOnly) {
                $io->note('No entities found with #[Anonymize] attribute');
            }

            return;
        }

        // Dispatch BeforeAnonymizeEvent
        $eventDispatcher = $this->container->has('event_dispatcher') ? $this->container->get('event_dispatcher') : null;
        if ($eventDispatcher !== null) {
            $entityClasses = array_keys($entities);
            $event = new BeforeAnonymizeEvent($em, $entityClasses, $dryRun);
            $eventDispatcher->dispatch($event);
            // Allow listeners to modify entity classes
            $entities = array_intersect_key($entities, array_flip($event->getEntityClasses()));
        }

        if (!$statsOnly) {
            $io->note(sprintf('Found %d entity(ies) to process', count($entities)));
            if ($debug) {
                $io->writeln('<comment>[DEBUG]</comment> Entity list:');
                foreach (array_keys($entities) as $entityName) {
                    $io->writeln(sprintf('  - %s', $entityName));
                }
            }
        }

        $totalProcessed = 0;
        $totalUpdated = 0;

        // Process each entity
        foreach ($entities as $className => $entityData) {
            $metadata = $entityData['metadata'];
            $reflection = $entityData['reflection'];
            $attribute = $entityData['attribute'];

            // Check if connection matches
            if (null !== $attribute->connection) {
                $connectionName = $em->getConnection()->getDatabase();
                // Note: This is a simplified check. You might need to adjust based on your setup
            }

            // Interactive confirmation for each entity
            if ($interactive && !$statsOnly) {
                // Get property count for summary
                $propertyCount = count($entityData['properties'] ?? []);
                $io->writeln(sprintf('Entity: <info>%s</info> (table: <comment>%s</comment>, properties: <info>%d</info>)', $className, $metadata->getTableName(), $propertyCount));
                
                if ($verbose && isset($entityData['properties'])) {
                    $io->writeln('  Properties to anonymize:');
                    foreach ($entityData['properties'] as $propName => $propData) {
                        $fakerType = $propData['attribute']->type ?? 'unknown';
                        $io->writeln(sprintf('    - <info>%s</info> (<comment>%s</comment>)', $propName, $fakerType));
                    }
                }
                $io->newLine();

                if (!$io->confirm(sprintf('Do you want to process entity <info>%s</info>?', $className), true)) {
                    $io->note(sprintf('Skipping entity: %s', $className));
                    continue;
                }
                $io->newLine();
            }

            if (!$statsOnly) {
                $io->writeln(sprintf('Processing entity: <info>%s</info>', $className));
            }

            if ($debug) {
                $io->writeln(sprintf('<comment>[DEBUG]</comment> Entity metadata: table=%s', $metadata->getTableName()));
            }

            // Get anonymizable properties
            $properties = $anonymizeService->getAnonymizableProperties($reflection);

            if (empty($properties)) {
                if (!$statsOnly) {
                    $io->writeln('  No properties found with #[AnonymizeProperty] attribute');
                }
                if ($debug) {
                    $io->writeln('<comment>[DEBUG]</comment> Skipping entity (no anonymizable properties)');
                }
                continue;
            }

            if (!$statsOnly) {
                $io->writeln(sprintf('  Found %d property(ies) to anonymize', count($properties)));
            }

            if ($verbose || $debug) {
                $io->writeln('  Properties to anonymize:');
                foreach ($properties as $propertyData) {
                    $property = $propertyData['property'];
                    $attribute = $propertyData['attribute'];
                    $weight = $propertyData['weight'] ?? 'N/A';
                    $io->writeln(sprintf('    - %s (type: %s, weight: %s)', $property->getName(), $attribute->type, $weight));
                    if ($debug) {
                        if (!empty($attribute->includePatterns)) {
                            $io->writeln(sprintf('      Include patterns: %s', json_encode($attribute->includePatterns)));
                        }
                        if (!empty($attribute->excludePatterns)) {
                            $io->writeln(sprintf('      Exclude patterns: %s', json_encode($attribute->excludePatterns)));
                        }
                        if (!empty($attribute->options)) {
                            $io->writeln(sprintf('      Options: %s', json_encode($attribute->options)));
                        }
                    }
                }
            }

            // Get total records count for progress bar
            $tableName = $metadata->getTableName();
            $connection = $em->getConnection();
            $countQuery = sprintf('SELECT COUNT(*) as total FROM %s', $connection->quoteSingleIdentifier($tableName));
            $totalRecords = (int) $connection->fetchOne($countQuery);

            if ($debug) {
                $io->writeln(sprintf('<comment>[DEBUG]</comment> Total records in table "%s": %d', $tableName, $totalRecords));
            }

            // Create progress bar if not in stats-only mode and progress is enabled
            $noProgress = $input !== null && $input->getOption('no-progress');
            $progressBar = null;
            if (!$statsOnly && !$noProgress && $totalRecords > 0 && $output !== null) {
                $progressBar = new ProgressBar($output, $totalRecords);
                $progressBar->setFormat('  %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %message%');
                $progressBar->setMessage(sprintf('Processing %s...', $className));
                $progressBar->start();
            }

            // Create progress callback
            $progressCallback = null;
            if ($progressBar !== null) {
                $progressCallback = function (int $current, int $total, string $message) use ($progressBar): void {
                    $progressBar->setProgress($current);
                    $progressBar->setMessage($message);
                };
            }

            // Anonymize entity
            $stats = $anonymizeService->anonymizeEntity(
                $em,
                $metadata,
                $reflection,
                $properties,
                $batchSize,
                $dryRun,
                $statistics,
                $progressCallback,
                $attribute // Pass entity-level Anonymize attribute for filtering
            );

            // Finish progress bar
            if ($progressBar !== null) {
                $progressBar->finish();
                $output->writeln('');
            }

            // Record statistics
            $statistics->recordEntity(
                $className,
                $managerName,
                $stats['processed'],
                $stats['updated'],
                $stats['propertyStats'] ?? []
            );

            // Accumulate totals
            $totalProcessed += $stats['processed'];
            $totalUpdated += $stats['updated'];

            if (!$statsOnly) {
                $io->writeln(
                    sprintf(
                        '  Processed: %d records, Updated: %d records',
                        $stats['processed'],
                        $stats['updated']
                    )
                );
            }

            if ($verbose || $debug) {
                if (!empty($stats['propertyStats'])) {
                    $io->writeln('  Property statistics:');
                    foreach ($stats['propertyStats'] as $propertyName => $count) {
                        $io->writeln(sprintf('    - %s: %d anonymized', $propertyName, $count));
                    }
                }
            }

            if ($debug) {
                $io->writeln(sprintf(
                    '<comment>[DEBUG]</comment> Entity processing completed: %d processed, %d updated, %d skipped',
                    $stats['processed'],
                    $stats['updated'],
                    $stats['processed'] - $stats['updated']
                ));
            }
        }

        // Dispatch AfterAnonymizeEvent
        if ($eventDispatcher !== null) {
            $entityClasses = array_keys($entities);
            $event = new AfterAnonymizeEvent($em, $entityClasses, $totalProcessed, $totalUpdated, $dryRun);
            $eventDispatcher->dispatch($event);
        }
    }

    /**
     * Displays anonymization statistics.
     *
     * @param SymfonyStyle $io The I/O helper
     * @param AnonymizeStatistics $statistics The statistics
     * @param bool $statsOnly If true, show only statistics
     * @param string|null $statsJson Path to export JSON statistics
     * @param string|null $statsCsv Path to export CSV statistics
     */
    private function displayStatistics(
        SymfonyStyle $io,
        AnonymizeStatistics $statistics,
        bool $statsOnly,
        ?string $statsJson,
        ?string $statsCsv = null
    ): void {
        $summary = $statistics->getSummary();
        $entities = $statistics->getEntities();

        // Export to JSON if requested
        if (null !== $statsJson) {
            $json = $statistics->toJson();
            file_put_contents($statsJson, $json);
            $io->success(sprintf('Statistics exported to JSON: %s', $statsJson));
        }

        // Export to CSV if requested
        if (null !== $statsCsv) {
            $csv = $statistics->toCsv();
            file_put_contents($statsCsv, $csv);
            $io->success(sprintf('Statistics exported to CSV: %s', $statsCsv));
        }

        // Display summary
        $io->title('Anonymization Statistics');

        $io->section('Summary');
        $summaryRows = [
            ['Total Entities', (string) $summary['total_entities']],
            ['Total Processed', (string) $summary['total_processed']],
            ['Total Updated', (string) $summary['total_updated']],
            ['Total Skipped', (string) $summary['total_skipped']],
            ['Duration', $summary['duration_formatted']],
            ['Average per Second', (string) $summary['average_per_second']],
        ];
        
        // Add success rate if we have processed records
        if ($summary['total_processed'] > 0) {
            $successRate = round(($summary['total_updated'] / $summary['total_processed']) * 100, 2);
            $summaryRows[] = ['Success Rate', sprintf('%.2f%%', $successRate)];
        }
        
        $io->table(['Metric', 'Value'], $summaryRows);

        // Display entity details
        if (!empty($entities)) {
            $io->section('Entity Details');

            $rows = [];
            foreach ($entities as $entityData) {
                $successRate = $entityData['processed'] > 0
                    ? round(($entityData['updated'] / $entityData['processed']) * 100, 2) . '%'
                    : 'N/A';
                
                $rows[] = [
                    $entityData['entity'],
                    $entityData['connection'],
                    (string) $entityData['processed'],
                    (string) $entityData['updated'],
                    (string) $entityData['skipped'],
                    $successRate,
                ];
            }

            $io->table(
                ['Entity', 'Connection', 'Processed', 'Updated', 'Skipped', 'Success Rate'],
                $rows
            );

            // Display property statistics
            $io->section('Property Statistics');
            foreach ($entities as $entityData) {
                if (empty($entityData['properties'])) {
                    continue;
                }

                $io->writeln(sprintf('<info>%s</info> (%s):', $entityData['entity'], $entityData['connection']));

                $propertyRows = [];
                foreach ($entityData['properties'] as $property => $count) {
                    $propertyRows[] = [$property, $count];
                }

                $io->table(['Property', 'Anonymized Count'], $propertyRows);
                $io->newLine();
            }
        }

        // Final message
        if (!$statsOnly) {
            $io->success(
                sprintf(
                    'Anonymization complete! Processed: %d records, Updated: %d records in %s',
                    $summary['total_processed'],
                    $summary['total_updated'],
                    $summary['duration_formatted']
                )
            );
        }
    }

    /**
     * Gets a parameter from the container.
     *
     * @param string $name The parameter name
     * @param mixed $default The default value if parameter doesn't exist
     * @return mixed The parameter value
     */
    private function getParameter(string $name, mixed $default = null): mixed
    {
        if ($this->container->has('kernel')) {
            $kernel = $this->container->get('kernel');
            $reflection = new \ReflectionClass($kernel);
            if ($reflection->hasProperty('container')) {
                $property = $reflection->getProperty('container');
                $property->setAccessible(true);
                $kernelContainer = $property->getValue($kernel);
                if ($kernelContainer instanceof \Symfony\Component\DependencyInjection\Container) {
                    if (method_exists($kernelContainer, 'hasParameter') && $kernelContainer->hasParameter($name)) {
                        return $kernelContainer->getParameter($name);
                    }
                }
            }
        }

        return $default;
    }
}
