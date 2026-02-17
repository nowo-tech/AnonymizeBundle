<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use InvalidArgumentException;
use Nowo\AnonymizeBundle\Faker\MapFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for MapFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class MapFakerTest extends TestCase
{
    /**
     * Test that MapFaker returns mapped value when original is in map.
     */
    public function testGenerateReturnsMappedValue(): void
    {
        $faker = new MapFaker();
        $map   = [
            'active'   => 'status_a',
            'inactive' => 'status_b',
            'pending'  => 'status_c',
        ];

        $this->assertEquals('status_a', $faker->generate(['map' => $map, 'original_value' => 'active']));
        $this->assertEquals('status_b', $faker->generate(['map' => $map, 'original_value' => 'inactive']));
        $this->assertEquals('status_c', $faker->generate(['map' => $map, 'original_value' => 'pending']));
    }

    /**
     * Test that MapFaker returns default when original is not in map.
     */
    public function testGenerateReturnsDefaultWhenNotInMap(): void
    {
        $faker = new MapFaker();
        $map   = ['active' => 'status_a', 'inactive' => 'status_b'];

        $result = $faker->generate([
            'map'            => $map,
            'original_value' => 'unknown',
            'default'        => 'status_unknown',
        ]);
        $this->assertEquals('status_unknown', $result);
    }

    /**
     * Test that MapFaker returns original value when not in map and no default.
     */
    public function testGenerateReturnsOriginalWhenNotInMapAndNoDefault(): void
    {
        $faker = new MapFaker();
        $map   = ['active' => 'status_a'];

        $result = $faker->generate(['map' => $map, 'original_value' => 'other']);
        $this->assertEquals('other', $result);
    }

    /**
     * Test that MapFaker throws exception when map option is missing.
     */
    public function testGenerateThrowsExceptionWhenMapMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MapFaker requires a "map" option');

        $faker = new MapFaker();
        $faker->generate(['original_value' => 'active']);
    }

    /**
     * Test that MapFaker throws exception when map is empty.
     */
    public function testGenerateThrowsExceptionWhenMapEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MapFaker requires a "map" option');

        $faker = new MapFaker();
        $faker->generate(['map' => [], 'original_value' => 'active']);
    }

    /**
     * Test that MapFaker works with numeric keys and null default.
     */
    public function testGenerateWithNumericKeysAndNullDefault(): void
    {
        $faker = new MapFaker();
        $map   = [1 => 'one', 2 => 'two', 3 => 'three'];

        $this->assertEquals('two', $faker->generate(['map' => $map, 'original_value' => 2]));
        $this->assertNull($faker->generate(['map' => $map, 'original_value' => 99, 'default' => null]));
    }
}
