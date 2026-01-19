<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Nowo\AnonymizeBundle\Enum\SymfonyService;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\AnonymizeStatistics;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Specific connections to process (default: all)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be anonymized without making changes')
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, 'Batch size for processing records', $this->batchSize)
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Locale for Faker generator', $this->locale)
            ->addOption('stats-json', null, InputOption::VALUE_OPTIONAL, 'Export statistics to JSON file')
            ->addOption('stats-only', null, InputOption::VALUE_NONE, 'Show only statistics summary')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command anonymizes database records based on Doctrine attributes.

  <info>php %command.full_name%</info>

This command will:
  1. Scan all Doctrine connections for entities with the #[Anonymize] attribute
  2. Process properties marked with #[AnonymizeProperty] attribute
  3. Anonymize values using Faker generators
  4. Respect weight ordering (lower weights first, then alphabetical)
  5. Apply inclusion/exclusion patterns

Options:
  --connection, -c    Process only specific connections (can be used multiple times)
  --dry-run          Show what would be anonymized without making changes
  --batch-size, -b   Number of records to process in each batch (default: 100)
  --locale, -l       Locale for Faker generator (default: en_US)
  --stats-json       Export statistics to JSON file
  --stats-only       Show only statistics summary (suppress detailed output)

Examples:
  <info>php %command.full_name%</info>
  <info>php %command.full_name% --dry-run</info>
  <info>php %command.full_name% --connection default --connection secondary</info>
  <info>php %command.full_name% --batch-size 50 --locale en_US</info>
  <info>php %command.full_name% --stats-json stats.json</info>
  <info>php %command.full_name% --stats-only</info>
HELP
            );
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

        // Check if running in dev environment
        $kernel = $this->container->get('kernel');
        $environment = $kernel->getEnvironment();

        if (!\in_array($environment, ['dev', 'test'], true)) {
            $io->error(sprintf(
                'This command can only be executed in "dev" or "test" environment. Current environment: "%s".',
                $environment
            ));
            $io->warning('This bundle is intended for development purposes only and should not be used in production.');

            return Command::FAILURE;
        }

        // Get options
        $connections = $input->getOption('connection') ?: $this->connections;
        $dryRun = $input->getOption('dry-run') || $this->dryRun;
        $batchSize = (int) ($input->getOption('batch-size') ?: $this->batchSize);
        $locale = $input->getOption('locale') ?: $this->locale;

        if ($dryRun) {
            $io->warning('DRY RUN MODE: No changes will be made to the database');
        }

        // Get Doctrine registry
        $doctrine = $this->container->get(SymfonyService::DOCTRINE);

        // Get all entity manager names (not connection names)
        $allManagers = $doctrine->getManagerNames();
        $managersToProcess = empty($connections) ? array_keys($allManagers) : array_intersect(array_keys($allManagers), $connections);

        if (empty($managersToProcess)) {
            $io->error('No entity managers found to process.');

            return Command::FAILURE;
        }

        // Initialize services
        $fakerFactory = new FakerFactory($locale, $this->container);
        $patternMatcher = new PatternMatcher();
        $anonymizeService = new AnonymizeService($fakerFactory, $patternMatcher);
        $statistics = new AnonymizeStatistics();
        $statistics->start();

        $statsOnly = $input->getOption('stats-only');
        $statsJson = $input->getOption('stats-json');

        // Process each entity manager
        foreach ($managersToProcess as $managerName) {
            if (!$statsOnly) {
                $io->section(sprintf('Processing entity manager: %s', $managerName));
            }

            try {
                $em = $doctrine->getManager($managerName);
                $this->processConnection($io, $em, $anonymizeService, $batchSize, $dryRun, $managerName, $statistics, $statsOnly);
            } catch (\Exception $e) {
                $io->error(sprintf('Error processing entity manager %s: %s', $managerName, $e->getMessage()));

                return Command::FAILURE;
            }
        }

        $statistics->stop();

        // Display statistics
        $this->displayStatistics($io, $statistics, $statsOnly, $statsJson);

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
        bool $statsOnly = false
    ): void {

        // Get all anonymizable entities
        $entities = $anonymizeService->getAnonymizableEntities($em);

        if (empty($entities)) {
            if (!$statsOnly) {
                $io->note('No entities found with #[Anonymize] attribute');
            }

            return;
        }

        if (!$statsOnly) {
            $io->note(sprintf('Found %d entity(ies) to process', count($entities)));
        }

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

            if (!$statsOnly) {
                $io->writeln(sprintf('Processing entity: <info>%s</info>', $className));
            }

            // Get anonymizable properties
            $properties = $anonymizeService->getAnonymizableProperties($reflection);

            if (empty($properties)) {
                if (!$statsOnly) {
                    $io->writeln('  No properties found with #[AnonymizeProperty] attribute');
                }
                continue;
            }

            if (!$statsOnly) {
                $io->writeln(sprintf('  Found %d property(ies) to anonymize', count($properties)));
            }

            // Anonymize entity
            $stats = $anonymizeService->anonymizeEntity(
                $em,
                $metadata,
                $reflection,
                $properties,
                $batchSize,
                $dryRun,
                $statistics
            );

            // Record statistics
            $statistics->recordEntity(
                $className,
                $managerName,
                $stats['processed'],
                $stats['updated'],
                $stats['propertyStats'] ?? []
            );

            if (!$statsOnly) {
                $io->writeln(
                    sprintf(
                        '  Processed: %d records, Updated: %d records',
                        $stats['processed'],
                        $stats['updated']
                    )
                );
            }
        }
    }

    /**
     * Displays anonymization statistics.
     *
     * @param SymfonyStyle $io The I/O helper
     * @param AnonymizeStatistics $statistics The statistics
     * @param bool $statsOnly If true, show only statistics
     * @param string|null $statsJson Path to export JSON statistics
     */
    private function displayStatistics(
        SymfonyStyle $io,
        AnonymizeStatistics $statistics,
        bool $statsOnly,
        ?string $statsJson
    ): void {
        $summary = $statistics->getSummary();
        $entities = $statistics->getEntities();

        // Export to JSON if requested
        if (null !== $statsJson) {
            $json = $statistics->toJson();
            file_put_contents($statsJson, $json);
            $io->success(sprintf('Statistics exported to: %s', $statsJson));
        }

        // Display summary
        $io->title('Anonymization Statistics');

        $io->section('Summary');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Entities', $summary['total_entities']],
                ['Total Processed', $summary['total_processed']],
                ['Total Updated', $summary['total_updated']],
                ['Total Skipped', $summary['total_skipped']],
                ['Duration', $summary['duration_formatted']],
                ['Average per Second', $summary['average_per_second']],
            ]
        );

        // Display entity details
        if (!empty($entities)) {
            $io->section('Entity Details');

            $rows = [];
            foreach ($entities as $entityData) {
                $rows[] = [
                    $entityData['entity'],
                    $entityData['connection'],
                    $entityData['processed'],
                    $entityData['updated'],
                    $entityData['skipped'],
                ];
            }

            $io->table(
                ['Entity', 'Connection', 'Processed', 'Updated', 'Skipped'],
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
}
