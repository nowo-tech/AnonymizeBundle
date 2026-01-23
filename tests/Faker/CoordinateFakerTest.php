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

    /**
     * Test that CoordinateFaker handles invalid format gracefully.
     */
    public function testGenerateWithInvalidFormat(): void
    {
        $faker = new CoordinateFaker('en_US');
        // Invalid format should default to string
        $coords = $faker->generate(['format' => 'invalid']);

        $this->assertIsString($coords);
        $this->assertStringContainsString(',', $coords);
    }

    /**
     * Test that CoordinateFaker handles zero precision.
     */
    public function testGenerateWithZeroPrecision(): void
    {
        $faker = new CoordinateFaker('en_US');
        $coords = $faker->generate(['precision' => 0]);

        $this->assertIsString($coords);
        $parts = explode(',', $coords);
        $this->assertCount(2, $parts);
        // With precision 0, should be whole numbers
        $this->assertStringNotContainsString('.', $parts[0]);
        $this->assertStringNotContainsString('.', $parts[1]);
    }

    /**
     * Test that CoordinateFaker handles high precision.
     */
    public function testGenerateWithHighPrecision(): void
    {
        $faker = new CoordinateFaker('en_US');
        $coords = $faker->generate(['precision' => 10]);

        $this->assertIsString($coords);
        $parts = explode(',', $coords);
        $this->assertCount(2, $parts);
    }

    /**
     * Test that CoordinateFaker generates different coordinates.
     */
    public function testGenerateDifferentCoordinates(): void
    {
        $faker = new CoordinateFaker('en_US');
        $coords = [];
        
        for ($i = 0; $i < 10; $i++) {
            $coords[] = $faker->generate();
        }
        
        // Should have some variation
        $uniqueCoords = array_unique($coords);
        $this->assertGreaterThan(1, count($uniqueCoords));
    }

    /**
     * Test that CoordinateFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new CoordinateFaker('en_US');
        $this->assertInstanceOf(CoordinateFaker::class, $faker);
    }

    /**
     * Test that CoordinateFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new CoordinateFaker('es_ES');
        $coords = $faker->generate();

        $this->assertIsString($coords);
        $this->assertStringContainsString(',', $coords);
    }
}
