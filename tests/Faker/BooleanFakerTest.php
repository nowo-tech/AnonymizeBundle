<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\BooleanFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for BooleanFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class BooleanFakerTest extends TestCase
{
    /**
     * Test that BooleanFaker generates a valid boolean.
     */
    public function testGenerate(): void
    {
        $faker = new BooleanFaker('en_US');
        $value = $faker->generate();

        $this->assertIsBool($value);
    }

    /**
     * Test that BooleanFaker respects true_probability option (100%).
     */
    public function testGenerateAlwaysTrue(): void
    {
        $faker = new BooleanFaker('en_US');
        $value = $faker->generate(['true_probability' => 100]);

        $this->assertTrue($value);
    }

    /**
     * Test that BooleanFaker respects true_probability option (0%).
     */
    public function testGenerateAlwaysFalse(): void
    {
        $faker = new BooleanFaker('en_US');
        $value = $faker->generate(['true_probability' => 0]);

        $this->assertFalse($value);
    }

    /**
     * Test that BooleanFaker clamps probability values.
     */
    public function testGenerateWithClampedProbability(): void
    {
        $faker = new BooleanFaker('en_US');
        // Test with out-of-range values
        $value1 = $faker->generate(['true_probability' => 150]);
        $value2 = $faker->generate(['true_probability' => -50]);

        $this->assertIsBool($value1);
        $this->assertIsBool($value2);
    }
}
