<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Command;

use Nowo\AnonymizeBundle\Service\AnonymizationHistoryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to view and manage anonymization history.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsCommand(
    name: 'nowo:anonymize:history',
    description: 'View and manage anonymization history'
)]
final class AnonymizationHistoryCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> command allows you to view and manage anonymization history.

This command provides:
  1. List all anonymization runs
  2. View details of a specific run
  3. Compare two runs
  4. Cleanup old runs

Examples:
  <info>php %command.full_name%</info>
  List all anonymization runs

  <info>php %command.full_name% --limit 10</info>
  List the last 10 runs

  <info>php %command.full_name% --run-id abc123</info>
  View details of a specific run

  <info>php %command.full_name% --compare abc123 def456</info>
  Compare two runs

  <info>php %command.full_name% --cleanup --days 30</info>
  Delete runs older than 30 days
HELP
            )
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit number of runs to display', null)
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'Filter by connection name', null)
            ->addOption('run-id', null, InputOption::VALUE_OPTIONAL, 'View details of a specific run', null)
            ->addOption('compare', null, InputOption::VALUE_OPTIONAL, 'Compare two runs (comma-separated run IDs)', null)
            ->addOption('cleanup', null, InputOption::VALUE_NONE, 'Cleanup old runs')
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Number of days to keep (for cleanup)', 30)
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Get history directory from parameter
        $historyDir = $this->getHistoryDir();
        $historyService = new AnonymizationHistoryService($historyDir);

        // Handle cleanup
        if ($input->getOption('cleanup')) {
            $days = (int) ($input->getOption('days') ?? 30);
            $deleted = $historyService->cleanup($days);
            $io->success(sprintf('Cleaned up %d old run(s) (kept runs from last %d days)', $deleted, $days));
            
            return Command::SUCCESS;
        }

        // Handle comparison
        if ($input->getOption('compare') !== null) {
            $runIds = explode(',', $input->getOption('compare'));
            if (count($runIds) !== 2) {
                $io->error('Comparison requires exactly 2 run IDs (comma-separated)');
                
                return Command::FAILURE;
            }
            
            $comparison = $historyService->compareRuns(trim($runIds[0]), trim($runIds[1]));
            if ($comparison === null) {
                $io->error('One or both runs not found');
                
                return Command::FAILURE;
            }
            
            if ($input->getOption('json')) {
                $output->writeln(json_encode($comparison, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                
                return Command::SUCCESS;
            }
            
            $this->displayComparison($io, $comparison);
            
            return Command::SUCCESS;
        }

        // Handle single run view
        if ($input->getOption('run-id') !== null) {
            $run = $historyService->getRun($input->getOption('run-id'));
            if ($run === null) {
                $io->error(sprintf('Run with ID "%s" not found', $input->getOption('run-id')));
                
                return Command::FAILURE;
            }
            
            if ($input->getOption('json')) {
                $output->writeln(json_encode($run, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                
                return Command::SUCCESS;
            }
            
            $this->displayRun($io, $run);
            
            return Command::SUCCESS;
        }

        // List all runs
        $limit = $input->getOption('limit') !== null ? (int) $input->getOption('limit') : null;
        $connection = $input->getOption('connection');
        $runs = $historyService->getRuns($limit, $connection);
        
        if (empty($runs)) {
            $io->info('No anonymization runs found in history.');
            
            return Command::SUCCESS;
        }
        
        if ($input->getOption('json')) {
            $output->writeln(json_encode($runs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            
            return Command::SUCCESS;
        }
        
        $this->displayRunsList($io, $runs);
        
        return Command::SUCCESS;
    }

    /**
     * Display list of runs.
     *
     * @param SymfonyStyle $io
     * @param array<int, array<string, mixed>> $runs
     */
    private function displayRunsList(SymfonyStyle $io, array $runs): void
    {
        $io->title('Anonymization History');
        
        $rows = [];
        foreach ($runs as $run) {
            $global = $run['statistics']['global'] ?? [];
            $rows[] = [
                substr($run['id'] ?? 'unknown', 0, 12),
                $run['datetime'] ?? 'N/A',
                (string) ($global['total_entities'] ?? 0),
                (string) ($global['total_processed'] ?? 0),
                (string) ($global['total_updated'] ?? 0),
                $this->formatDuration($global['duration'] ?? 0),
            ];
        }
        
        $io->table(
            ['Run ID', 'Date/Time', 'Entities', 'Processed', 'Updated', 'Duration'],
            $rows
        );
        
        $io->note(sprintf('Use --run-id <id> to view details of a specific run'));
    }

    /**
     * Display details of a single run.
     *
     * @param SymfonyStyle $io
     * @param array<string, mixed> $run
     */
    private function displayRun(SymfonyStyle $io, array $run): void
    {
        $io->title(sprintf('Anonymization Run: %s', substr($run['id'] ?? 'unknown', 0, 12)));
        
        $io->section('Run Information');
        $io->table(
            ['Property', 'Value'],
            [
                ['Run ID', $run['id'] ?? 'N/A'],
                ['Date/Time', $run['datetime'] ?? 'N/A'],
                ['Environment', $run['metadata']['environment'] ?? 'N/A'],
                ['PHP Version', $run['metadata']['php_version'] ?? 'N/A'],
                ['Symfony Version', $run['metadata']['symfony_version'] ?? 'N/A'],
            ]
        );
        
        $global = $run['statistics']['global'] ?? [];
        $io->section('Global Statistics');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Entities', $global['total_entities'] ?? 0],
                ['Total Processed', $global['total_processed'] ?? 0],
                ['Total Updated', $global['total_updated'] ?? 0],
                ['Total Skipped', $global['total_skipped'] ?? 0],
                ['Duration', $this->formatDuration($global['duration'] ?? 0)],
                ['Start Time', isset($global['start_time']) ? date('Y-m-d H:i:s', (int) $global['start_time']) : 'N/A'],
                ['End Time', isset($global['end_time']) ? date('Y-m-d H:i:s', (int) $global['end_time']) : 'N/A'],
            ]
        );
        
        $entities = $run['statistics']['entities'] ?? [];
        if (!empty($entities)) {
            $io->section('Entity Statistics');
            $entityRows = [];
            foreach ($entities as $entityData) {
                $successRate = $entityData['processed'] > 0
                    ? round(($entityData['updated'] / $entityData['processed']) * 100, 2) . '%'
                    : 'N/A';
                
                $entityRows[] = [
                    $entityData['entity'] ?? 'N/A',
                    $entityData['connection'] ?? 'N/A',
                    (string) ($entityData['processed'] ?? 0),
                    (string) ($entityData['updated'] ?? 0),
                    (string) ($entityData['skipped'] ?? 0),
                    $successRate,
                ];
            }
            
            $io->table(
                ['Entity', 'Connection', 'Processed', 'Updated', 'Skipped', 'Success Rate'],
                $entityRows
            );
        }
    }

    /**
     * Display comparison between two runs.
     *
     * @param SymfonyStyle $io
     * @param array<string, mixed> $comparison
     */
    private function displayComparison(SymfonyStyle $io, array $comparison): void
    {
        $io->title('Run Comparison');
        
        $io->section('Run Information');
        $io->table(
            ['Property', 'Run 1', 'Run 2'],
            [
                ['Run ID', substr($comparison['run1']['id'] ?? 'unknown', 0, 12), substr($comparison['run2']['id'] ?? 'unknown', 0, 12)],
                ['Date/Time', $comparison['run1']['datetime'] ?? 'N/A', $comparison['run2']['datetime'] ?? 'N/A'],
            ]
        );
        
        $global = $comparison['global'] ?? [];
        $io->section('Global Statistics Comparison');
        $globalRows = [];
        foreach ($global as $metric => $data) {
            $diff = $data['diff'] ?? 0;
            $diffFormatted = $diff >= 0 ? sprintf('+%s', $diff) : (string) $diff;
            $globalRows[] = [
                ucfirst(str_replace('_', ' ', $metric)),
                (string) ($data['run1'] ?? 0),
                (string) ($data['run2'] ?? 0),
                $diffFormatted,
            ];
        }
        
        $io->table(
            ['Metric', 'Run 1', 'Run 2', 'Difference'],
            $globalRows
        );
        
        $entities = $comparison['entities'] ?? [];
        if (!empty($entities)) {
            $io->section('Entity Statistics Comparison');
            $entityRows = [];
            foreach ($entities as $entityKey => $entityData) {
                $entityRows[] = [
                    $entityData['entity'] ?? 'N/A',
                    $entityData['connection'] ?? 'N/A',
                    (string) ($entityData['processed']['run1'] ?? 0),
                    (string) ($entityData['processed']['run2'] ?? 0),
                    (string) ($entityData['processed']['diff'] ?? 0),
                    (string) ($entityData['updated']['run1'] ?? 0),
                    (string) ($entityData['updated']['run2'] ?? 0),
                    (string) ($entityData['updated']['diff'] ?? 0),
                ];
            }
            
            $io->table(
                ['Entity', 'Connection', 'Processed (R1)', 'Processed (R2)', 'Diff', 'Updated (R1)', 'Updated (R2)', 'Diff'],
                $entityRows
            );
        }
    }

    /**
     * Format duration in seconds to human-readable format.
     */
    private function formatDuration(float $seconds): string
    {
        if ($seconds < 1) {
            return sprintf('%.2f ms', $seconds * 1000);
        }
        
        if ($seconds < 60) {
            return sprintf('%.2f s', $seconds);
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return sprintf('%d m %.2f s', $minutes, $remainingSeconds);
    }

    /**
     * Get history directory from container parameters.
     */
    private function getHistoryDir(): string
    {
        // Try to get from environment or use default
        $historyDir = $_ENV['NOWO_ANONYMIZE_HISTORY_DIR'] ?? '%kernel.project_dir%/var/anonymize_history';
        
        // If running in Symfony context, resolve kernel.project_dir
        if (str_contains($historyDir, '%kernel.project_dir%')) {
            if (file_exists(__DIR__ . '/../../../../var')) {
                // We're in a Symfony project
                $projectRoot = realpath(__DIR__ . '/../../../../');
                $historyDir = str_replace('%kernel.project_dir%', $projectRoot, $historyDir);
            } else {
                // Fallback
                $historyDir = str_replace('%kernel.project_dir%', getcwd() ?: '.', $historyDir);
            }
        }
        
        return $historyDir;
    }
}
