<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Service;

use Nowo\AnonymizeBundle\Service\AnonymizeStatistics;
use Nowo\AnonymizeBundle\Service\AnonymizeStatisticsDisplay;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

use function count;

/**
 * Unit tests for AnonymizeStatisticsDisplay.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizeStatisticsDisplayTest extends TestCase
{
    private function createStatistics(
        int $totalProcessed = 10,
        int $totalUpdated = 8,
        float $duration = 1.5,
        array $entities = []
    ): AnonymizeStatistics {
        $stats = new AnonymizeStatistics();
        $stats->start();
        $ref        = new ReflectionClass($stats);
        $globalProp = $ref->getProperty('globalStats');
        $globalProp->setAccessible(true);
        $g                    = $globalProp->getValue($stats);
        $g['total_entities']  = $entities !== [] ? count($entities) : 0;
        $g['total_processed'] = $totalProcessed;
        $g['total_updated']   = $totalUpdated;
        $g['total_skipped']   = $totalProcessed - $totalUpdated;
        $g['duration']        = $duration;
        $g['start_time']      = 1.0;
        $g['end_time']        = 1.0 + $duration;
        $globalProp->setValue($stats, $g);
        $entityProp = $ref->getProperty('entityStats');
        $entityProp->setAccessible(true);
        $entityProp->setValue($stats, $entities);

        return $stats;
    }

    /**
     * Display shows summary table and includes Success Rate when total_processed > 0.
     */
    public function testDisplayShowsSummaryWithSuccessRateWhenProcessedGreaterThanZero(): void
    {
        $stats   = $this->createStatistics(10, 8, 1.0, []);
        $out     = new BufferedOutput();
        $io      = new SymfonyStyle(new ArrayInput([]), $out);
        $display = new AnonymizeStatisticsDisplay();

        $display->display($io, $stats, false, null, null);

        $text = $out->fetch();
        $this->assertStringContainsString('Anonymization Statistics', $text);
        $this->assertStringContainsString('Summary', $text);
        $this->assertStringContainsString('Success Rate', $text);
        $this->assertStringContainsString('80.00%', $text);
        $this->assertStringContainsString('Anonymization complete!', $text);
    }

    /**
     * Display shows summary without Success Rate row when total_processed is 0.
     */
    public function testDisplayShowsSummaryWithoutSuccessRateWhenProcessedZero(): void
    {
        $stats   = $this->createStatistics(0, 0, 0.0, []);
        $out     = new BufferedOutput();
        $io      = new SymfonyStyle(new ArrayInput([]), $out);
        $display = new AnonymizeStatisticsDisplay();

        $display->display($io, $stats, false, null, null);

        $text = $out->fetch();
        $this->assertStringContainsString('Summary', $text);
        $this->assertStringNotContainsString('Success Rate', $text);
    }

    /**
     * Display exports JSON when statsJson path is provided.
     */
    public function testDisplayExportsJsonWhenStatsJsonProvided(): void
    {
        $stats = $this->createStatistics(5, 3, 0.5, []);
        $tmp   = tempnam(sys_get_temp_dir(), 'anon_json');
        $this->assertNotFalse($tmp);
        try {
            $out     = new BufferedOutput();
            $io      = new SymfonyStyle(new ArrayInput([]), $out);
            $display = new AnonymizeStatisticsDisplay();

            $display->display($io, $stats, true, $tmp, null);

            $this->assertFileExists($tmp);
            $content = file_get_contents($tmp);
            $this->assertJson($content);
            $decoded = json_decode($content, true);
            $this->assertSame(5, $decoded['global']['total_processed'] ?? null);
            $text = $out->fetch();
            $this->assertStringContainsString('Statistics exported to JSON', $text);
            $this->assertStringContainsString($tmp, $text);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Display exports CSV when statsCsv path is provided.
     */
    public function testDisplayExportsCsvWhenStatsCsvProvided(): void
    {
        $stats = $this->createStatistics(5, 3, 0.5, []);
        $tmp   = tempnam(sys_get_temp_dir(), 'anon_csv');
        $this->assertNotFalse($tmp);
        try {
            $out     = new BufferedOutput();
            $io      = new SymfonyStyle(new ArrayInput([]), $out);
            $display = new AnonymizeStatisticsDisplay();

            $display->display($io, $stats, true, null, $tmp);

            $this->assertFileExists($tmp);
            $content = file_get_contents($tmp);
            $this->assertStringContainsString('Section,Key,Value', $content);
            $this->assertStringContainsString('Global,Total Processed,5', $content);
            $text = $out->fetch();
            $this->assertStringContainsString('Statistics exported to CSV', $text);
            $this->assertStringContainsString($tmp, $text);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Display does not show final success message when statsOnly is true.
     */
    public function testDisplaySkipsFinalSuccessMessageWhenStatsOnlyTrue(): void
    {
        $stats   = $this->createStatistics(10, 8, 1.0, []);
        $out     = new BufferedOutput();
        $io      = new SymfonyStyle(new ArrayInput([]), $out);
        $display = new AnonymizeStatisticsDisplay();

        $display->display($io, $stats, true, null, null);

        $text = $out->fetch();
        $this->assertStringNotContainsString('Anonymization complete!', $text);
    }

    /**
     * Display shows entity details and property statistics when entities exist.
     */
    public function testDisplayShowsEntityDetailsAndPropertyStatistics(): void
    {
        $entities = [
            'App\Entity\User@default' => [
                'entity'     => 'App\Entity\User',
                'connection' => 'default',
                'processed'  => 10,
                'updated'    => 8,
                'skipped'    => 2,
                'properties' => ['email' => 8, 'name' => 8],
            ],
        ];
        $stats   = $this->createStatistics(10, 8, 1.0, $entities);
        $out     = new BufferedOutput();
        $io      = new SymfonyStyle(new ArrayInput([]), $out);
        $display = new AnonymizeStatisticsDisplay();

        $display->display($io, $stats, false, null, null);

        $text = $out->fetch();
        $this->assertStringContainsString('Entity Details', $text);
        $this->assertStringContainsString('Property Statistics', $text);
        $this->assertStringContainsString('App\Entity\User', $text);
        $this->assertStringContainsString('email', $text);
        $this->assertStringContainsString('name', $text);
        $this->assertStringContainsString('Anonymized Count', $text);
    }

    /**
     * Display skips property block for entity with empty properties (continue branch).
     */
    public function testDisplaySkipsPropertyBlockForEntityWithEmptyProperties(): void
    {
        $entities = [
            'App\Entity\User@default' => [
                'entity'     => 'App\Entity\User',
                'connection' => 'default',
                'processed'  => 10,
                'updated'    => 8,
                'skipped'    => 2,
                'properties' => [],
            ],
        ];
        $stats   = $this->createStatistics(10, 8, 1.0, $entities);
        $out     = new BufferedOutput();
        $io      = new SymfonyStyle(new ArrayInput([]), $out);
        $display = new AnonymizeStatisticsDisplay();

        $display->display($io, $stats, false, null, null);

        $text = $out->fetch();
        $this->assertStringContainsString('Entity Details', $text);
        $this->assertStringContainsString('Property Statistics', $text);
        $this->assertStringContainsString('App\Entity\User', $text);
        $this->assertStringNotContainsString('Anonymized Count', $text);
    }

    /**
     * Display shows N/A success rate for entity with zero processed.
     */
    public function testDisplayShowsNAForEntitySuccessRateWhenProcessedZero(): void
    {
        $entities = [
            'App\Entity\User@default' => [
                'entity'     => 'App\Entity\User',
                'connection' => 'default',
                'processed'  => 0,
                'updated'    => 0,
                'skipped'    => 0,
                'properties' => [],
            ],
        ];
        $stats   = $this->createStatistics(0, 0, 0.0, $entities);
        $out     = new BufferedOutput();
        $io      = new SymfonyStyle(new ArrayInput([]), $out);
        $display = new AnonymizeStatisticsDisplay();

        $display->display($io, $stats, true, null, null);

        $text = $out->fetch();
        $this->assertStringContainsString('N/A', $text);
    }
}
