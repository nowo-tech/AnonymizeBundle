<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\AgeFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for AgeFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AgeFakerTest extends TestCase
{
    /**
     * Test that AgeFaker generates a valid age.
     */
    public function testGenerate(): void
    {
        $faker = new AgeFaker('en_US');
        $age = $faker->generate();

        $this->assertIsInt($age);
        $this->assertGreaterThanOrEqual(18, $age);
        $this->assertLessThanOrEqual(100, $age);
    }

    /**
     * Test that AgeFaker respects min and max options.
     */
    public function testGenerateWithOptions(): void
    {
        $faker = new AgeFaker('en_US');
        $age = $faker->generate(['min' => 25, 'max' => 50]);

        $this->assertIsInt($age);
        $this->assertGreaterThanOrEqual(25, $age);
        $this->assertLessThanOrEqual(50, $age);
    }
}
