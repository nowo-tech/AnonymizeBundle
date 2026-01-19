<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\CoordinateFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for CoordinateFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class CoordinateFakerTest extends TestCase
{
    /**
     * Test that CoordinateFaker generates valid coordinates.
     */
    public function testGenerate(): void
    {
        $faker = new CoordinateFaker('en_US');
        $coords = $faker->generate();

        $this->assertIsString($coords);
        $this->assertStringContainsString(',', $coords);
    }

    /**
     * Test that CoordinateFaker generates array format.
     */
    public function testGenerateArrayFormat(): void
    {
        $faker = new CoordinateFaker('en_US');
        $coords = $faker->generate(['format' => 'array']);

        $this->assertIsArray($coords);
        $this->assertArrayHasKey('latitude', $coords);
        $this->assertArrayHasKey('longitude', $coords);
        $this->assertIsFloat($coords['latitude']);
        $this->assertIsFloat($coords['longitude']);
        $this->assertGreaterThanOrEqual(-90.0, $coords['latitude']);
        $this->assertLessThanOrEqual(90.0, $coords['latitude']);
        $this->assertGreaterThanOrEqual(-180.0, $coords['longitude']);
        $this->assertLessThanOrEqual(180.0, $coords['longitude']);
    }

    /**
     * Test that CoordinateFaker generates JSON format.
     */
    public function testGenerateJsonFormat(): void
    {
        $faker = new CoordinateFaker('en_US');
        $coords = $faker->generate(['format' => 'json']);

        $this->assertIsString($coords);
        $decoded = json_decode($coords, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('latitude', $decoded);
        $this->assertArrayHasKey('longitude', $decoded);
    }

    /**
     * Test that CoordinateFaker respects precision option.
     */
    public function testGenerateWithPrecision(): void
    {
        $faker = new CoordinateFaker('en_US');
        $coords = $faker->generate(['precision' => 2]);

        $this->assertIsString($coords);
        $parts = explode(',', $coords);
        $this->assertCount(2, $parts);
        // Check that decimal precision is 2
        $latParts = explode('.', $parts[0]);
        if (count($latParts) === 2) {
            $this->assertLessThanOrEqual(2, strlen($latParts[1]));
        }
    }

    /**
     * Test that CoordinateFaker respects bounds options.
     */
    public function testGenerateWithBounds(): void
    {
        $faker = new CoordinateFaker('en_US');
        $coords = $faker->generate([
            'format' => 'array',
            'min_lat' => 40.0,
            'max_lat' => 50.0,
            'min_lng' => -5.0,
            'max_lng' => 5.0,
        ]);

        $this->assertIsArray($coords);
        $this->assertGreaterThanOrEqual(40.0, $coords['latitude']);
        $this->assertLessThanOrEqual(50.0, $coords['latitude']);
        $this->assertGreaterThanOrEqual(-5.0, $coords['longitude']);
        $this->assertLessThanOrEqual(5.0, $coords['longitude']);
    }
}
