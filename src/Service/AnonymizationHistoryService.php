<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

use function array_slice;
use function dirname;
use function is_string;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const PHP_VERSION;

/**
 * Service for managing anonymization history.
 *
 * Stores and retrieves anonymization run metadata for tracking and comparison.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AnonymizationHistoryService
{
    /**
     * @var Filesystem The filesystem service for file operations
     */
    private readonly Filesystem $filesystem;

    /**
     * @var string The directory where anonymization history is stored
     */
    private readonly string $historyDir;

    /**
     * @var ClockInterface The clock used to get the current time
     */
    private readonly ClockInterface $clock;

    /**
     * Optional provider for Symfony version (used in tests to cover 'unknown' branch).
     *
     * @var callable(): string|null
     */
    private $symfonyVersionProvider;

    /**
     * Optional provider to check if Kernel class exists (used in tests to cover 'unknown' branch when no version provider).
     *
     * @var callable(): bool|null
     */
    private $kernelClassExistsProvider;

    /**
     * Creates a new AnonymizationHistoryService instance.
     *
     * @param string $historyDir The directory where anonymization history is stored
     * @param ClockInterface|null $clock Clock for getting the current time; uses system clock when null
     * @param callable(): string|null $symfonyVersionProvider Optional; when set, used instead of detecting Kernel (for tests)
     * @param callable(): bool|null $kernelClassExistsProvider Optional; when set, used instead of class_exists(Kernel::class) (for tests)
     */
    public function __construct(string $historyDir, ?ClockInterface $clock = null, ?callable $symfonyVersionProvider = null, ?callable $kernelClassExistsProvider = null)
    {
        $this->filesystem = new Filesystem();
        $this->historyDir = rtrim($historyDir, '/');
        $this->clock      = $clock ?? new class implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable();
            }
        };
        $this->symfonyVersionProvider    = $symfonyVersionProvider;
        $this->kernelClassExistsProvider = $kernelClassExistsProvider;
    }

    /**
     * Save anonymization run to history.
     *
     * @param array<string, mixed> $statistics Statistics from AnonymizeStatistics
     * @param array<string, mixed> $metadata Additional metadata (command options, environment, etc.)
     *
     * @return string The history file path
     */
    public function saveRun(array $statistics, array $metadata = []): string
    {
        // Ensure history directory exists
        if (!is_dir($this->historyDir)) {
            $this->filesystem->mkdir($this->historyDir, 0o700);
        }

        // Create history entry
        $historyEntry = [
            'id'        => $this->generateRunId(),
            'timestamp' => $this->clock->now()->getTimestamp(),
            'datetime'  => date('Y-m-d H:i:s', $this->clock->now()->getTimestamp()),
            'metadata'  => array_merge([
                // @igor-ignore - $_SERVER is request-scoped in FrankenPHP; preferred over $_ENV for worker safety.
                'environment'     => ($_SERVER['APP_ENV'] ?? getenv('APP_ENV')) ?: 'unknown',
                'php_version'     => PHP_VERSION,
                'symfony_version' => $this->getSymfonyVersion(),
            ], $metadata),
            'statistics' => $statistics,
        ];

        // Save to file
        // Same clock instant as the entry timestamp, so the index entry (built from it) points at this file
        $filename = sprintf('run_%s_%s.json', date('Y-m-d_His', $historyEntry['timestamp']), $historyEntry['id']);
        $filePath = $this->historyDir . '/' . $filename;

        $this->writeOwnerOnly($filePath, json_encode($historyEntry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

        // Update index file for quick access
        $this->updateIndex($historyEntry);

        return $filePath;
    }

    /**
     * Get all history runs.
     *
     * @param int|null $limit Maximum number of runs to return
     * @param string|null $connection Filter by connection name
     *
     * @return array<int, array<string, mixed>> Array of history entries
     */
    public function getRuns(?int $limit = null, ?string $connection = null): array
    {
        $indexFile = $this->historyDir . '/index.json';

        if (!file_exists($indexFile)) {
            return [];
        }

        $index = json_decode(file_get_contents($indexFile) ?: '[]', true) ?? [];
        $runs  = [];

        foreach ($index as $entry) {
            // Filter by connection if specified
            if ($connection !== null) {
                $hasConnection = false;
                if (isset($entry['statistics']['entities'])) {
                    foreach ($entry['statistics']['entities'] as $entityStat) {
                        if (isset($entityStat['connection']) && $entityStat['connection'] === $connection) {
                            $hasConnection = true;
                            break;
                        }
                    }
                }
                if (!$hasConnection) {
                    continue;
                }
            }

            // Load full run data (only from run files inside the history directory)
            $runFile = $this->trustedRunFile($entry['file'] ?? null);
            if ($runFile !== null) {
                $runData = json_decode(file_get_contents($runFile) ?: '{}', true);
                if ($runData !== null) {
                    $runData['file'] = $runFile;
                    $runs[]          = $runData;
                }
            } else {
                // Fallback to index data if file doesn't exist
                $runs[] = $entry;
            }
        }

        // Sort by timestamp (newest first)
        usort($runs, static fn (array $a, array $b): int => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));

        // Apply limit
        if ($limit !== null && $limit > 0) {
            return array_slice($runs, 0, $limit);
        }

        return $runs;
    }

    /**
     * Get a specific run by ID.
     *
     * @param string $runId The run ID
     *
     * @return array<string, mixed>|null The run data or null if not found
     */
    public function getRun(string $runId): ?array
    {
        $indexFile = $this->historyDir . '/index.json';

        if (!file_exists($indexFile)) {
            return null;
        }

        $index = json_decode(file_get_contents($indexFile) ?: '[]', true) ?? [];

        foreach ($index as $entry) {
            if (isset($entry['id']) && $entry['id'] === $runId) {
                $runFile = $this->trustedRunFile($entry['file'] ?? null);
                if ($runFile !== null) {
                    $runData = json_decode(file_get_contents($runFile) ?: '{}', true);
                    if ($runData !== null) {
                        return $runData;
                    }
                }

                return $entry;
            }
        }

        return null;
    }

    /**
     * Compare two runs.
     *
     * @param string $runId1 First run ID
     * @param string $runId2 Second run ID
     *
     * @return array<string, mixed> Comparison data
     */
    public function compareRuns(string $runId1, string $runId2): ?array
    {
        $run1 = $this->getRun($runId1);
        $run2 = $this->getRun($runId2);

        if ($run1 === null || $run2 === null) {
            return null;
        }

        return [
            'run1' => [
                'id'        => $run1['id'],
                'timestamp' => $run1['timestamp'],
                'datetime'  => $run1['datetime'],
            ],
            'run2' => [
                'id'        => $run2['id'],
                'timestamp' => $run2['timestamp'],
                'datetime'  => $run2['datetime'],
            ],
            'global' => $this->compareGlobalStats(
                $run1['statistics']['global'] ?? [],
                $run2['statistics']['global'] ?? [],
            ),
            'entities' => $this->compareEntityStats(
                $run1['statistics']['entities'] ?? [],
                $run2['statistics']['entities'] ?? [],
            ),
        ];
    }

    /**
     * Delete old runs (cleanup).
     *
     * @param int $daysToKeep Number of days to keep
     *
     * @return int Number of runs deleted
     */
    public function cleanup(int $daysToKeep = 30): int
    {
        $cutoffTime = $this->clock->now()->getTimestamp() - ($daysToKeep * 24 * 60 * 60);
        $runs       = $this->getRuns();
        $deleted    = 0;

        foreach ($runs as $run) {
            // getRuns() only exposes "file" for run files inside the history directory
            if (isset($run['timestamp']) && $run['timestamp'] < $cutoffTime && isset($run['file']) && $this->trustedRunFile($run['file']) !== null) {
                // @igor-ignore - Filesystem I/O on disk files; not in-process shared state mutation.
                $this->filesystem->remove($run['file']);
                ++$deleted;
            }
        }

        // Rebuild index
        $this->rebuildIndex();

        return $deleted;
    }

    /**
     * Returns the path when it is a regular run file directly inside the history directory, else null.
     *
     * index.json is a plain file on disk: its "file" entries are not trusted to read or delete arbitrary paths.
     */
    private function trustedRunFile(mixed $file): ?string
    {
        if (!is_string($file) || preg_match('/^run_[0-9_-]+_[0-9a-f]+\.json$/', basename($file)) !== 1 || is_link($file) || !is_file($file)) {
            return null;
        }

        $directory = realpath(dirname($file));

        return $directory !== false && $directory === realpath($this->historyDir) ? $file : null;
    }

    /**
     * Writes a history file readable by its owner only (run statistics describe personal data tables).
     */
    private function writeOwnerOnly(string $path, string $content): void
    {
        $this->filesystem->dumpFile($path, $content);
        $this->filesystem->chmod($path, 0o600);
    }

    /**
     * Generates a unique run ID.
     *
     * @return string A unique 12-character hexadecimal run ID
     */
    private function generateRunId(): string
    {
        return substr(md5(uniqid((string) $this->clock->now()->getTimestamp(), true)), 0, 12);
    }

    /**
     * Updates the index file with a new history entry.
     *
     * @param array<string, mixed> $entry The history entry to add
     */
    private function updateIndex(array $entry): void
    {
        $indexFile = $this->historyDir . '/index.json';
        $index     = [];

        if (file_exists($indexFile)) {
            $index = json_decode(file_get_contents($indexFile) ?: '[]', true) ?? [];
        }

        // Add entry to index
        $indexEntry = [
            'id'        => $entry['id'],
            'timestamp' => $entry['timestamp'],
            'datetime'  => $entry['datetime'],
            'file'      => $this->historyDir . '/' . sprintf('run_%s_%s.json', date('Y-m-d_His', $entry['timestamp']), $entry['id']),
            'summary'   => [
                'total_entities'  => $entry['statistics']['global']['total_entities'] ?? 0,
                'total_processed' => $entry['statistics']['global']['total_processed'] ?? 0,
                'total_updated'   => $entry['statistics']['global']['total_updated'] ?? 0,
            ],
        ];

        $index[] = $indexEntry;

        // Sort by timestamp (newest first) and limit to 1000 entries
        usort($index, static fn (array $a, array $b): int => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));

        $index = array_slice($index, 0, 1000);

        $this->writeOwnerOnly($indexFile, json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]');
    }

    /**
     * Rebuilds the index file from existing history files.
     *
     * Scans the history directory for all run files and rebuilds the index.json file.
     */
    private function rebuildIndex(): void
    {
        if (!is_dir($this->historyDir)) {
            return;
        }

        $index = [];
        $files = glob($this->historyDir . '/run_*.json') ?: [];

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file) ?: '{}', true);
            if ($data !== null && isset($data['id'])) {
                $index[] = [
                    'id'        => $data['id'],
                    'timestamp' => $data['timestamp'],
                    'datetime'  => $data['datetime'],
                    'file'      => $file,
                    'summary'   => [
                        'total_entities'  => $data['statistics']['global']['total_entities'] ?? 0,
                        'total_processed' => $data['statistics']['global']['total_processed'] ?? 0,
                        'total_updated'   => $data['statistics']['global']['total_updated'] ?? 0,
                    ],
                ];
            }
        }

        // Sort by timestamp (newest first)
        usort($index, static fn (array $a, array $b): int => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));

        $indexFile = $this->historyDir . '/index.json';
        $this->writeOwnerOnly($indexFile, json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]');
    }

    /**
     * Compare global statistics between two runs.
     *
     * @param array<string, mixed> $stats1 First run stats
     * @param array<string, mixed> $stats2 Second run stats
     *
     * @return array<string, mixed> Comparison data
     */
    private function compareGlobalStats(array $stats1, array $stats2): array
    {
        return [
            'total_entities' => [
                'run1' => $stats1['total_entities'] ?? 0,
                'run2' => $stats2['total_entities'] ?? 0,
                'diff' => ($stats2['total_entities'] ?? 0) - ($stats1['total_entities'] ?? 0),
            ],
            'total_processed' => [
                'run1' => $stats1['total_processed'] ?? 0,
                'run2' => $stats2['total_processed'] ?? 0,
                'diff' => ($stats2['total_processed'] ?? 0) - ($stats1['total_processed'] ?? 0),
            ],
            'total_updated' => [
                'run1' => $stats1['total_updated'] ?? 0,
                'run2' => $stats2['total_updated'] ?? 0,
                'diff' => ($stats2['total_updated'] ?? 0) - ($stats1['total_updated'] ?? 0),
            ],
            'duration' => [
                'run1' => $stats1['duration'] ?? 0,
                'run2' => $stats2['duration'] ?? 0,
                'diff' => ($stats2['duration'] ?? 0) - ($stats1['duration'] ?? 0),
            ],
        ];
    }

    /**
     * Compare entity statistics between two runs.
     *
     * @param array<string, mixed> $entities1 First run entities
     * @param array<string, mixed> $entities2 Second run entities
     *
     * @return array<string, mixed> Comparison data
     */
    private function compareEntityStats(array $entities1, array $entities2): array
    {
        $comparison  = [];
        $allEntities = array_unique(array_merge(array_keys($entities1), array_keys($entities2)));

        foreach ($allEntities as $entityKey) {
            $entity1 = $entities1[$entityKey] ?? null;
            $entity2 = $entities2[$entityKey] ?? null;

            $comparison[$entityKey] = [
                'entity'     => $entity1['entity'] ?? $entity2['entity'] ?? 'unknown',
                'connection' => $entity1['connection'] ?? $entity2['connection'] ?? 'unknown',
                'processed'  => [
                    'run1' => $entity1['processed'] ?? 0,
                    'run2' => $entity2['processed'] ?? 0,
                    'diff' => ($entity2['processed'] ?? 0) - ($entity1['processed'] ?? 0),
                ],
                'updated' => [
                    'run1' => $entity1['updated'] ?? 0,
                    'run2' => $entity2['updated'] ?? 0,
                    'diff' => ($entity2['updated'] ?? 0) - ($entity1['updated'] ?? 0),
                ],
            ];
        }

        return $comparison;
    }

    /**
     * Gets the Symfony version.
     *
     * @return string The Symfony version string, or 'unknown' if not available
     */
    private function getSymfonyVersion(): string
    {
        if ($this->symfonyVersionProvider !== null) {
            return ($this->symfonyVersionProvider)();
        }
        $kernelExists = $this->kernelClassExistsProvider !== null
            ? ($this->kernelClassExistsProvider)()
            : class_exists(Kernel::class);
        if ($kernelExists) {
            return Kernel::VERSION;
        }

        return 'unknown';
    }
}
