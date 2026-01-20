<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

/**
 * Statistics collector for anonymization process.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AnonymizeStatistics
{
    /**
     * @var array<string, array{entity: string, connection: string, processed: int, updated: int, properties: array<string, int>}>
     */
    private array $entityStats = [];

    /**
     * @var array<string, int> Global statistics
     */
    private array $globalStats = [
        'total_entities' => 0,
        'total_processed' => 0,
        'total_updated' => 0,
        'total_skipped' => 0,
        'start_time' => 0,
        'end_time' => 0,
        'duration' => 0,
    ];

    /**
     * Start time tracking.
     */
    public function start(): void
    {
        $this->globalStats['start_time'] = microtime(true);
    }

    /**
     * Stop time tracking and calculate duration.
     */
    public function stop(): void
    {
        $this->globalStats['end_time'] = microtime(true);
        if ($this->globalStats['start_time'] > 0) {
            $this->globalStats['duration'] = $this->globalStats['end_time'] - $this->globalStats['start_time'];
        }
    }

    /**
     * Record statistics for an entity.
     *
     * @param string $entityClass The entity class name
     * @param string $connection The connection name
     * @param int $processed Number of records processed
     * @param int $updated Number of records updated
     * @param array<string, int> $propertyStats Statistics per property
     */
    public function recordEntity(
        string $entityClass,
        string $connection,
        int $processed,
        int $updated,
        array $propertyStats = []
    ): void {
        $key = $entityClass . '@' . $connection;

        if (!isset($this->entityStats[$key])) {
            $this->entityStats[$key] = [
                'entity' => $entityClass,
                'connection' => $connection,
                'processed' => 0,
                'updated' => 0,
                'skipped' => 0,
                'properties' => [],
            ];
            $this->globalStats['total_entities']++;
        }

        $this->entityStats[$key]['processed'] += $processed;
        $this->entityStats[$key]['updated'] += $updated;
        $this->entityStats[$key]['skipped'] += ($processed - $updated);
        $this->entityStats[$key]['properties'] = array_merge_recursive(
            $this->entityStats[$key]['properties'],
            $propertyStats
        );

        $this->globalStats['total_processed'] += $processed;
        $this->globalStats['total_updated'] += $updated;
        $this->globalStats['total_skipped'] += ($processed - $updated);
    }

    /**
     * Record statistics for a property.
     *
     * @param string $entityClass The entity class name
     * @param string $connection The connection name
     * @param string $propertyName The property name
     * @param int $count Number of times this property was anonymized
     */
    public function recordProperty(
        string $entityClass,
        string $connection,
        string $propertyName,
        int $count = 1
    ): void {
        $key = $entityClass . '@' . $connection;

        if (!isset($this->entityStats[$key])) {
            $this->entityStats[$key] = [
                'entity' => $entityClass,
                'connection' => $connection,
                'processed' => 0,
                'updated' => 0,
                'skipped' => 0,
                'properties' => [],
            ];
        }

        if (!isset($this->entityStats[$key]['properties'][$propertyName])) {
            $this->entityStats[$key]['properties'][$propertyName] = 0;
        }

        $this->entityStats[$key]['properties'][$propertyName] += $count;
    }

    /**
     * Get all statistics.
     *
     * @return array<string, mixed> Complete statistics
     */
    public function getAll(): array
    {
        return [
            'global' => $this->globalStats,
            'entities' => $this->entityStats,
        ];
    }

    /**
     * Get global statistics.
     *
     * @return array<string, mixed> Global statistics
     */
    public function getGlobal(): array
    {
        return $this->globalStats;
    }

    /**
     * Get entity statistics.
     *
     * @return array<string, array<string, mixed>> Entity statistics
     */
    public function getEntities(): array
    {
        return $this->entityStats;
    }

    /**
     * Get statistics as JSON.
     *
     * @param int $flags JSON encoding flags
     * @return string JSON encoded statistics
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): string
    {
        return json_encode($this->getAll(), $flags);
    }

    /**
     * Get statistics as CSV.
     *
     * @return string CSV encoded statistics
     */
    public function toCsv(): string
    {
        $lines = [];

        // Global statistics header
        $lines[] = 'Section,Key,Value';
        $lines[] = 'Global,Total Entities,' . $this->globalStats['total_entities'];
        $lines[] = 'Global,Total Processed,' . $this->globalStats['total_processed'];
        $lines[] = 'Global,Total Updated,' . $this->globalStats['total_updated'];
        $lines[] = 'Global,Total Skipped,' . $this->globalStats['total_skipped'];
        $lines[] = 'Global,Duration (seconds),' . round($this->globalStats['duration'], 2);
        $lines[] = 'Global,Duration (formatted),' . $this->formatDuration($this->globalStats['duration']);
        $lines[] = 'Global,Average per Second,' . ($this->globalStats['duration'] > 0
            ? round($this->globalStats['total_processed'] / $this->globalStats['duration'], 2)
            : 0);
        $lines[] = 'Global,Success Rate (%),' . ($this->globalStats['total_processed'] > 0
            ? round(($this->globalStats['total_updated'] / $this->globalStats['total_processed']) * 100, 2)
            : 0);

        // Entity statistics
        $lines[] = '';
        $lines[] = 'Entity,Connection,Processed,Updated,Skipped,Success Rate (%)';
        foreach ($this->entityStats as $entityData) {
            $successRate = $entityData['processed'] > 0
                ? round(($entityData['updated'] / $entityData['processed']) * 100, 2)
                : 0;

            $lines[] = sprintf(
                '%s,%s,%d,%d,%d,%.2f',
                $entityData['entity'],
                $entityData['connection'],
                $entityData['processed'],
                $entityData['updated'],
                $entityData['skipped'],
                $successRate
            );
        }

        // Property statistics
        if (!empty($this->entityStats)) {
            $lines[] = '';
            $lines[] = 'Entity,Connection,Property,Anonymized Count';
            foreach ($this->entityStats as $entityData) {
                if (empty($entityData['properties'])) {
                    continue;
                }

                foreach ($entityData['properties'] as $property => $count) {
                    $lines[] = sprintf(
                        '%s,%s,%s,%d',
                        $entityData['entity'],
                        $entityData['connection'],
                        $property,
                        $count
                    );
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Get statistics summary.
     *
     * @return array<string, mixed> Summary statistics
     */
    public function getSummary(): array
    {
        return [
            'total_entities' => $this->globalStats['total_entities'],
            'total_processed' => $this->globalStats['total_processed'],
            'total_updated' => $this->globalStats['total_updated'],
            'total_skipped' => $this->globalStats['total_skipped'],
            'duration_seconds' => round($this->globalStats['duration'], 2),
            'duration_formatted' => $this->formatDuration($this->globalStats['duration']),
            'average_per_second' => $this->globalStats['duration'] > 0
                ? round($this->globalStats['total_processed'] / $this->globalStats['duration'], 2)
                : 0,
        ];
    }

    /**
     * Format duration in human-readable format.
     *
     * @param float $seconds Duration in seconds
     * @return string Formatted duration
     */
    private function formatDuration(float $seconds): string
    {
        if ($seconds < 1) {
            return round($seconds * 1000, 2) . ' ms';
        }

        if ($seconds < 60) {
            return round($seconds, 2) . ' s';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return sprintf('%d m %d s', (int) $minutes, (int) $remainingSeconds);
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%d h %d m %d s', (int) $hours, (int) $remainingMinutes, (int) $remainingSeconds);
    }

    /**
     * Reset all statistics.
     */
    public function reset(): void
    {
        $this->entityStats = [];
        $this->globalStats = [
            'total_entities' => 0,
            'total_processed' => 0,
            'total_updated' => 0,
            'total_skipped' => 0,
            'start_time' => 0,
            'end_time' => 0,
            'duration' => 0,
        ];
    }
}
