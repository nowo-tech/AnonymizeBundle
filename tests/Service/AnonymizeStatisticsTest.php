<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Service;

use Nowo\AnonymizeBundle\Service\AnonymizeStatistics;
use PHPUnit\Framework\TestCase;

/**
 * Test case for AnonymizeStatistics.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizeStatisticsTest extends TestCase
{
    /**
     * Test that statistics can be recorded and retrieved.
     */
    public function testRecordAndRetrieveStatistics(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();

        $stats->recordEntity('App\Entity\User', 'default', 10, 8, ['email' => 8, 'name' => 8]);
        $stats->recordEntity('App\Entity\Customer', 'default', 5, 3, ['email' => 3]);

        $stats->stop();

        $all = $stats->getAll();
        $this->assertArrayHasKey('global', $all);
        $this->assertArrayHasKey('entities', $all);
        $this->assertEquals(2, $all['global']['total_entities']);
        $this->assertEquals(15, $all['global']['total_processed']);
        $this->assertEquals(11, $all['global']['total_updated']);
        $this->assertEquals(4, $all['global']['total_skipped']);
    }

    /**
     * Test that statistics can be exported to JSON.
     */
    public function testToJson(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        $stats->recordEntity('App\Entity\User', 'default', 10, 8);
        $stats->stop();

        $json = $stats->toJson();
        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('global', $decoded);
        $this->assertArrayHasKey('entities', $decoded);
    }

    /**
     * Test that statistics can be exported to CSV.
     */
    public function testToCsv(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        $stats->recordEntity('App\Entity\User', 'default', 10, 8, ['email' => 8, 'name' => 8]);
        $stats->recordEntity('App\Entity\Customer', 'default', 5, 3, ['email' => 3]);
        $stats->stop();

        $csv = $stats->toCsv();
        $this->assertIsString($csv);
        $this->assertStringContainsString('Section,Key,Value', $csv);
        $this->assertStringContainsString('Global,Total Entities', $csv);
        $this->assertStringContainsString('Entity,Connection,Processed,Updated,Skipped,Success Rate (%)', $csv);
        $this->assertStringContainsString('App\Entity\User,default,10,8,2', $csv);
        $this->assertStringContainsString('App\Entity\Customer,default,5,3,2', $csv);
        $this->assertStringContainsString('Entity,Connection,Property,Anonymized Count', $csv);
        $this->assertStringContainsString('App\Entity\User,default,email,8', $csv);
        $this->assertStringContainsString('App\Entity\User,default,name,8', $csv);
        $this->assertStringContainsString('App\Entity\Customer,default,email,3', $csv);
    }

    /**
     * Test that summary statistics are correct.
     */
    public function testGetSummary(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        usleep(100000); // Sleep 0.1 seconds
        $stats->recordEntity('App\Entity\User', 'default', 10, 8);
        $stats->stop();

        $summary = $stats->getSummary();
        $this->assertArrayHasKey('total_entities', $summary);
        $this->assertArrayHasKey('total_processed', $summary);
        $this->assertArrayHasKey('total_updated', $summary);
        $this->assertArrayHasKey('duration_seconds', $summary);
        $this->assertArrayHasKey('duration_formatted', $summary);
        $this->assertArrayHasKey('average_per_second', $summary);
        $this->assertEquals(1, $summary['total_entities']);
        $this->assertEquals(10, $summary['total_processed']);
        $this->assertEquals(8, $summary['total_updated']);
    }

    /**
     * Test that statistics can be reset.
     */
    public function testReset(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        $stats->recordEntity('App\Entity\User', 'default', 10, 8);
        $stats->stop();

        $this->assertEquals(1, $stats->getGlobal()['total_entities']);

        $stats->reset();

        $this->assertEquals(0, $stats->getGlobal()['total_entities']);
        $this->assertEquals(0, $stats->getGlobal()['total_processed']);
        $this->assertEquals(0, $stats->getGlobal()['total_updated']);
    }

    /**
     * Test property statistics recording.
     */
    public function testRecordProperty(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->recordProperty('App\Entity\User', 'default', 'email', 5);
        $stats->recordProperty('App\Entity\User', 'default', 'email', 3);

        $entities = $stats->getEntities();
        $this->assertArrayHasKey('App\Entity\User@default', $entities);
        $this->assertEquals(8, $entities['App\Entity\User@default']['properties']['email']);
    }

    /**
     * Test getGlobal returns correct statistics.
     */
    public function testGetGlobal(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        $stats->recordEntity('App\Entity\User', 'default', 10, 8);
        $stats->stop();

        $global = $stats->getGlobal();
        $this->assertIsArray($global);
        $this->assertEquals(1, $global['total_entities']);
        $this->assertEquals(10, $global['total_processed']);
        $this->assertEquals(8, $global['total_updated']);
        $this->assertEquals(2, $global['total_skipped']);
    }

    /**
     * Test getEntities returns correct statistics.
     */
    public function testGetEntities(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        $stats->recordEntity('App\Entity\User', 'default', 10, 8, ['email' => 8]);
        $stats->recordEntity('App\Entity\Customer', 'postgres', 5, 3, ['email' => 3]);
        $stats->stop();

        $entities = $stats->getEntities();
        $this->assertIsArray($entities);
        $this->assertArrayHasKey('App\Entity\User@default', $entities);
        $this->assertArrayHasKey('App\Entity\Customer@postgres', $entities);
        $this->assertEquals(10, $entities['App\Entity\User@default']['processed']);
        $this->assertEquals(8, $entities['App\Entity\User@default']['updated']);
    }

    /**
     * Test statistics without start/stop.
     */
    public function testStatisticsWithoutStartStop(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->recordEntity('App\Entity\User', 'default', 10, 8);

        $all = $stats->getAll();
        $this->assertArrayHasKey('global', $all);
        $this->assertArrayHasKey('entities', $all);
    }

    /**
     * Test summary includes all required fields.
     */
    public function testGetSummaryIncludesAllFields(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        usleep(100000); // Sleep 0.1 seconds
        $stats->recordEntity('App\Entity\User', 'default', 10, 8);
        $stats->stop();

        $summary = $stats->getSummary();
        $this->assertArrayHasKey('total_entities', $summary);
        $this->assertArrayHasKey('total_processed', $summary);
        $this->assertArrayHasKey('total_updated', $summary);
        $this->assertArrayHasKey('duration_seconds', $summary);
        $this->assertArrayHasKey('duration_formatted', $summary);
        $this->assertArrayHasKey('average_per_second', $summary);
        $this->assertEquals(1, $summary['total_entities']);
        $this->assertEquals(10, $summary['total_processed']);
        $this->assertEquals(8, $summary['total_updated']);
    }

    /**
     * Test summary with zero processed.
     */
    public function testGetSummaryWithZeroProcessed(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        $stats->stop();

        $summary = $stats->getSummary();
        $this->assertEquals(0, $summary['total_entities']);
        $this->assertEquals(0, $summary['total_processed']);
        $this->assertEquals(0, $summary['total_updated']);
    }

    /**
     * Test formatDuration with milliseconds.
     */
    public function testFormatDurationWithMilliseconds(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        usleep(50000); // 0.05 seconds
        $stats->stop();

        $summary = $stats->getSummary();
        $this->assertStringContainsString('ms', $summary['duration_formatted']);
    }

    /**
     * Test formatDuration with seconds.
     */
    public function testFormatDurationWithSeconds(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        usleep(2000000); // 2 seconds
        $stats->stop();

        $summary = $stats->getSummary();
        $this->assertStringContainsString('s', $summary['duration_formatted']);
    }

    /**
     * Test formatDuration with minutes.
     */
    public function testFormatDurationWithMinutes(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        // Simulate 65 seconds by setting times directly
        $reflection = new \ReflectionClass($stats);
        $globalStatsProperty = $reflection->getProperty('globalStats');
        $globalStatsProperty->setAccessible(true);
        $globalStats = $globalStatsProperty->getValue($stats);
        $globalStats['start_time'] = microtime(true) - 65;
        $globalStats['end_time'] = microtime(true);
        $globalStats['duration'] = 65;
        $globalStatsProperty->setValue($stats, $globalStats);

        $summary = $stats->getSummary();
        $this->assertStringContainsString('m', $summary['duration_formatted']);
    }

    /**
     * Test formatDuration with hours.
     */
    public function testFormatDurationWithHours(): void
    {
        $stats = new AnonymizeStatistics();
        $reflection = new \ReflectionClass($stats);
        $globalStatsProperty = $reflection->getProperty('globalStats');
        $globalStatsProperty->setAccessible(true);
        $globalStats = $globalStatsProperty->getValue($stats);
        $globalStats['duration'] = 3665; // 1 hour 1 minute 5 seconds
        $globalStatsProperty->setValue($stats, $globalStats);

        $summary = $stats->getSummary();
        $this->assertStringContainsString('h', $summary['duration_formatted']);
    }

    /**
     * Test toCsv with empty properties.
     */
    public function testToCsvWithEmptyProperties(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        $stats->recordEntity('App\Entity\User', 'default', 10, 8, []);
        $stats->stop();

        $csv = $stats->toCsv();
        $this->assertIsString($csv);
        $this->assertStringContainsString('App\Entity\User,default,10,8,2', $csv);
        // The code checks if properties array is empty for each entity, so it should skip property rows
        // but the header might still be present if there are other entities with properties
        // In this case, we only have one entity with no properties, so no property rows should appear
        $lines = explode("\n", $csv);
        $propertyRowCount = 0;
        foreach ($lines as $line) {
            if (str_contains($line, 'App\Entity\User,default,') && str_contains($line, ',')) {
                $parts = explode(',', $line);
                // Property rows have 4 parts: Entity,Connection,Property,Count
                if (count($parts) === 4 && $parts[2] !== 'Property') {
                    $propertyRowCount++;
                }
            }
        }
        $this->assertEquals(0, $propertyRowCount, 'No property rows should appear when there are no properties');
    }

    /**
     * Test toCsv with multiple entities and properties.
     */
    public function testToCsvWithMultipleEntitiesAndProperties(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        $stats->recordEntity('App\Entity\User', 'default', 10, 8, ['email' => 8, 'name' => 8, 'phone' => 5]);
        $stats->recordEntity('App\Entity\Customer', 'postgres', 5, 3, ['email' => 3]);
        $stats->stop();

        $csv = $stats->toCsv();
        $this->assertIsString($csv);
        $this->assertStringContainsString('App\Entity\User,default,10,8,2', $csv);
        $this->assertStringContainsString('App\Entity\Customer,postgres,5,3,2', $csv);
        $this->assertStringContainsString('App\Entity\User,default,email,8', $csv);
        $this->assertStringContainsString('App\Entity\User,default,name,8', $csv);
        $this->assertStringContainsString('App\Entity\User,default,phone,5', $csv);
    }

    /**
     * Test toJson with custom flags.
     */
    public function testToJsonWithCustomFlags(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->recordEntity('App\Entity\User', 'default', 10, 8);

        $json = $stats->toJson(JSON_UNESCAPED_UNICODE);
        $this->assertIsString($json);
        $this->assertJson($json);
    }

    /**
     * Test recordEntity accumulates statistics correctly.
     */
    public function testRecordEntityAccumulatesStatistics(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->recordEntity('App\Entity\User', 'default', 10, 8, ['email' => 8]);
        $stats->recordEntity('App\Entity\User', 'default', 5, 3, ['email' => 3]);

        $entities = $stats->getEntities();
        $this->assertEquals(15, $entities['App\Entity\User@default']['processed']);
        $this->assertEquals(11, $entities['App\Entity\User@default']['updated']);
        $this->assertEquals(4, $entities['App\Entity\User@default']['skipped']);
    }

    /**
     * Test recordProperty creates entity if it doesn't exist.
     */
    public function testRecordPropertyCreatesEntityIfNotExists(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->recordProperty('App\Entity\User', 'default', 'email', 5);

        $entities = $stats->getEntities();
        $this->assertArrayHasKey('App\Entity\User@default', $entities);
        $this->assertEquals(5, $entities['App\Entity\User@default']['properties']['email']);
    }

    /**
     * Test stop without start.
     */
    public function testStopWithoutStart(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->stop();

        $global = $stats->getGlobal();
        $this->assertEquals(0, $global['duration']);
    }

    /**
     * Test toCsv calculates success rate correctly.
     */
    public function testToCsvCalculatesSuccessRate(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        $stats->recordEntity('App\Entity\User', 'default', 100, 75);
        $stats->stop();

        $csv = $stats->toCsv();
        $this->assertStringContainsString('75.00', $csv); // 75% success rate
    }

    /**
     * Test toCsv handles zero duration.
     */
    public function testToCsvHandlesZeroDuration(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->recordEntity('App\Entity\User', 'default', 10, 8);

        $csv = $stats->toCsv();
        $this->assertIsString($csv);
        $this->assertStringContainsString('Average per Second,0', $csv);
    }

    /**
     * Test formatDuration with exactly 1 second.
     */
    public function testFormatDurationWithExactlyOneSecond(): void
    {
        $stats = new AnonymizeStatistics();
        $reflection = new \ReflectionClass($stats);
        $globalStatsProperty = $reflection->getProperty('globalStats');
        $globalStatsProperty->setAccessible(true);
        $globalStats = $globalStatsProperty->getValue($stats);
        $globalStats['duration'] = 1.0;
        $globalStatsProperty->setValue($stats, $globalStats);

        $summary = $stats->getSummary();
        $this->assertStringContainsString('s', $summary['duration_formatted']);
        $this->assertStringNotContainsString('ms', $summary['duration_formatted']);
    }

    /**
     * Test formatDuration with exactly 60 seconds (1 minute).
     */
    public function testFormatDurationWithExactlySixtySeconds(): void
    {
        $stats = new AnonymizeStatistics();
        $reflection = new \ReflectionClass($stats);
        $globalStatsProperty = $reflection->getProperty('globalStats');
        $globalStatsProperty->setAccessible(true);
        $globalStats = $globalStatsProperty->getValue($stats);
        $globalStats['duration'] = 60.0;
        $globalStatsProperty->setValue($stats, $globalStats);

        $summary = $stats->getSummary();
        $this->assertStringContainsString('m', $summary['duration_formatted']);
    }

    /**
     * Test formatDuration with exactly 3600 seconds (1 hour).
     */
    public function testFormatDurationWithExactlyOneHour(): void
    {
        $stats = new AnonymizeStatistics();
        $reflection = new \ReflectionClass($stats);
        $globalStatsProperty = $reflection->getProperty('globalStats');
        $globalStatsProperty->setAccessible(true);
        $globalStats = $globalStatsProperty->getValue($stats);
        $globalStats['duration'] = 3600.0;
        $globalStatsProperty->setValue($stats, $globalStats);

        $summary = $stats->getSummary();
        $this->assertStringContainsString('h', $summary['duration_formatted']);
    }

    /**
     * Test formatDuration with zero seconds.
     */
    public function testFormatDurationWithZeroSeconds(): void
    {
        $stats = new AnonymizeStatistics();
        $reflection = new \ReflectionClass($stats);
        $globalStatsProperty = $reflection->getProperty('globalStats');
        $globalStatsProperty->setAccessible(true);
        $globalStats = $globalStatsProperty->getValue($stats);
        $globalStats['duration'] = 0.0;
        $globalStatsProperty->setValue($stats, $globalStats);

        $summary = $stats->getSummary();
        $this->assertStringContainsString('ms', $summary['duration_formatted']);
    }

    /**
     * Test toCsv handles zero processed records for success rate calculation.
     */
    public function testToCsvHandlesZeroProcessedForSuccessRate(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->start();
        $stats->stop();

        $csv = $stats->toCsv();
        $this->assertIsString($csv);
        $this->assertStringContainsString('Success Rate (%),0', $csv);
    }

    /**
     * Test toCsv handles entity with zero processed for success rate.
     */
    public function testToCsvHandlesEntityWithZeroProcessed(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->recordEntity('App\Entity\User', 'default', 0, 0);

        $csv = $stats->toCsv();
        $this->assertIsString($csv);
        $this->assertStringContainsString('App\Entity\User,default,0,0,0,0.00', $csv);
    }

    /**
     * Test recordEntity with empty property stats array.
     */
    public function testRecordEntityWithEmptyPropertyStats(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->recordEntity('App\Entity\User', 'default', 10, 8, []);

        $entities = $stats->getEntities();
        $this->assertArrayHasKey('App\Entity\User@default', $entities);
        $this->assertEmpty($entities['App\Entity\User@default']['properties']);
    }

    /**
     * Test recordProperty with count greater than 1.
     */
    public function testRecordPropertyWithCountGreaterThanOne(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->recordProperty('App\Entity\User', 'default', 'email', 10);

        $entities = $stats->getEntities();
        $this->assertEquals(10, $entities['App\Entity\User@default']['properties']['email']);
    }

    /**
     * Test recordProperty accumulates count correctly.
     */
    public function testRecordPropertyAccumulatesCount(): void
    {
        $stats = new AnonymizeStatistics();
        $stats->recordProperty('App\Entity\User', 'default', 'email', 5);
        $stats->recordProperty('App\Entity\User', 'default', 'email', 3);
        $stats->recordProperty('App\Entity\User', 'default', 'email', 2);

        $entities = $stats->getEntities();
        $this->assertEquals(10, $entities['App\Entity\User@default']['properties']['email']);
    }
}
