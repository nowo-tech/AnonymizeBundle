<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\NumericFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for NumericFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class NumericFakerTest extends TestCase
{
    /**
     * Test that NumericFaker generates a valid integer.
     */
    public function testGenerate(): void
    {
        $faker = new NumericFaker('en_US');
        $value = $faker->generate();

        $this->assertIsInt($value);
    }

    /**
     * Test that NumericFaker generates integer with min/max.
     */
    public function testGenerateIntWithRange(): void
    {
        $faker = new NumericFaker('en_US');
        $value = $faker->generate(['type' => 'int', 'min' => 10, 'max' => 20]);

        $this->assertIsInt($value);
        $this->assertGreaterThanOrEqual(10, $value);
        $this->assertLessThanOrEqual(20, $value);
    }

    /**
     * Test that NumericFaker generates float.
     */
    public function testGenerateFloat(): void
    {
        $faker = new NumericFaker('en_US');
        $value = $faker->generate(['type' => 'float']);

        $this->assertIsFloat($value);
    }

    /**
     * Test that NumericFaker generates float with precision.
     */
    public function testGenerateFloatWithPrecision(): void
    {
        $faker = new NumericFaker('en_US');
        $value = $faker->generate(['type' => 'float', 'precision' => 4, 'min' => 0, 'max' => 100]);

        $this->assertIsFloat($value);
        // Check precision (decimal places)
        $parts = explode('.', (string) $value);
        if (count($parts) === 2) {
            $this->assertLessThanOrEqual(4, strlen($parts[1]));
        }
    }

    /**
     * Test that NumericFaker generates float with range.
     */
    public function testGenerateFloatWithRange(): void
    {
        $faker = new NumericFaker('en_US');
        $value = $faker->generate(['type' => 'float', 'min' => 10.5, 'max' => 20.5]);

        $this->assertIsFloat($value);
        $this->assertGreaterThanOrEqual(10.5, $value);
        $this->assertLessThanOrEqual(20.5, $value);
    }
}
