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
        $age   = $faker->generate();

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
        $age   = $faker->generate(['min' => 25, 'max' => 50]);

        $this->assertIsInt($age);
        $this->assertGreaterThanOrEqual(25, $age);
        $this->assertLessThanOrEqual(50, $age);
    }

    /**
     * Test that AgeFaker respects distribution option (uniform).
     */
    public function testGenerateWithUniformDistribution(): void
    {
        $faker = new AgeFaker('en_US');
        $age   = $faker->generate(['distribution' => 'uniform', 'min' => 20, 'max' => 30]);

        $this->assertIsInt($age);
        $this->assertGreaterThanOrEqual(20, $age);
        $this->assertLessThanOrEqual(30, $age);
    }

    /**
     * Test that AgeFaker respects distribution option (normal).
     */
    public function testGenerateWithNormalDistribution(): void
    {
        $faker = new AgeFaker('en_US');
        $age   = $faker->generate([
            'distribution' => 'normal',
            'mean'         => 40,
            'std_dev'      => 10,
            'min'          => 18,
            'max'          => 100,
        ]);

        $this->assertIsInt($age);
        $this->assertGreaterThanOrEqual(18, $age);
        $this->assertLessThanOrEqual(100, $age);
    }

    /**
     * Test that AgeFaker respects mean and std_dev options.
     */
    public function testGenerateWithMeanAndStdDev(): void
    {
        $faker = new AgeFaker('en_US');
        $age   = $faker->generate([
            'distribution' => 'normal',
            'mean'         => 50,
            'std_dev'      => 5,
            'min'          => 30,
            'max'          => 70,
        ]);

        $this->assertIsInt($age);
        $this->assertGreaterThanOrEqual(30, $age);
        $this->assertLessThanOrEqual(70, $age);
    }

    /**
     * Test that AgeFaker handles min equal to max.
     */
    public function testGenerateWithMinEqualMax(): void
    {
        $faker = new AgeFaker('en_US');
        $age   = $faker->generate(['min' => 25, 'max' => 25]);

        $this->assertEquals(25, $age);
    }

    /**
     * Test that AgeFaker handles invalid distribution gracefully.
     */
    public function testGenerateWithInvalidDistribution(): void
    {
        $faker = new AgeFaker('en_US');
        // Invalid distribution should default to uniform
        $age = $faker->generate(['distribution' => 'invalid', 'min' => 20, 'max' => 30]);

        $this->assertIsInt($age);
        $this->assertGreaterThanOrEqual(20, $age);
        $this->assertLessThanOrEqual(30, $age);
    }

    /**
     * Test that AgeFaker handles normal distribution with extreme values.
     */
    public function testGenerateNormalWithExtremeValues(): void
    {
        $faker = new AgeFaker('en_US');
        $age   = $faker->generate([
            'distribution' => 'normal',
            'mean'         => 100,
            'std_dev'      => 50,
            'min'          => 0,
            'max'          => 150,
        ]);

        $this->assertIsInt($age);
        $this->assertGreaterThanOrEqual(0, $age);
        $this->assertLessThanOrEqual(150, $age);
    }

    /**
     * Test that AgeFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new AgeFaker('en_US');
        $this->assertInstanceOf(AgeFaker::class, $faker);
    }

    /**
     * Test that AgeFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new AgeFaker('es_ES');
        $age   = $faker->generate();

        $this->assertIsInt($age);
        $this->assertGreaterThanOrEqual(18, $age);
        $this->assertLessThanOrEqual(100, $age);
    }
}
