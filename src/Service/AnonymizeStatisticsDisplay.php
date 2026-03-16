<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

/**
 * Displays anonymization statistics and optionally exports them to JSON/CSV files.
 *
 * Extracted from AnonymizeCommand to allow unit testing of display and export logic.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AnonymizeStatisticsDisplay
{
    /**
     * Displays anonymization statistics and optionally exports them to files.
     *
     * @param SymfonyStyle $io The Symfony style output
     * @param AnonymizeStatistics $statistics The statistics collector
     * @param bool $statsOnly If true, show only statistics summary
     * @param string|null $statsJson Path to export JSON statistics file
     * @param string|null $statsCsv Path to export CSV statistics file
     */
    public function display(
        SymfonyStyle $io,
        AnonymizeStatistics $statistics,
        bool $statsOnly,
        ?string $statsJson,
        ?string $statsCsv = null
    ): void {
        $summary  = $statistics->getSummary();
        $entities = $statistics->getEntities();

        // Export to JSON if requested
        if ($statsJson !== null) {
            $json = $statistics->toJson();
            file_put_contents($statsJson, $json);
            $io->success(sprintf('Statistics exported to JSON: %s', $statsJson));
        }

        // Export to CSV if requested
        if ($statsCsv !== null) {
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
            $successRate   = round(($summary['total_updated'] / $summary['total_processed']) * 100, 2);
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
                $rows,
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
                    $summary['duration_formatted'],
                ),
            );
        }
    }
}
