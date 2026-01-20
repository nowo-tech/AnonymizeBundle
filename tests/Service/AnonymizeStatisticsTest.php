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
}
