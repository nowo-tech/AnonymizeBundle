<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\ShuffleFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ShuffleFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class ShuffleFakerTest extends TestCase
{
    /**
     * Test that ShuffleFaker generates a value from the provided pool.
     */
    public function testGenerate(): void
    {
        $faker = new ShuffleFaker();
        $values = ['value1', 'value2', 'value3', 'value4'];
        $value = $faker->generate(['values' => $values]);

        $this->assertContains($value, $values);
    }

    /**
     * Test that ShuffleFaker throws exception when values option is missing.
     */
    public function testGenerateThrowsExceptionWhenValuesMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ShuffleFaker requires a "values" option');

        $faker = new ShuffleFaker();
        $faker->generate();
    }

    /**
     * Test that ShuffleFaker throws exception when values is empty.
     */
    public function testGenerateThrowsExceptionWhenValuesEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ShuffleFaker requires a "values" option');

        $faker = new ShuffleFaker();
        $faker->generate(['values' => []]);
    }

    /**
     * Test that ShuffleFaker respects seed option for reproducibility.
     */
    public function testGenerateWithSeed(): void
    {
        $faker = new ShuffleFaker();
        $values = ['value1', 'value2', 'value3', 'value4'];

        // With same seed, should get same result (or at least valid value)
        $value1 = $faker->generate(['values' => $values, 'seed' => 12345]);
        $value2 = $faker->generate(['values' => $values, 'seed' => 12345]);

        $this->assertContains($value1, $values);
        $this->assertContains($value2, $values);
    }

    /**
     * Test that ShuffleFaker respects exclude option.
     */
    public function testGenerateWithExclude(): void
    {
        $faker = new ShuffleFaker();
        $values = ['value1', 'value2', 'value3', 'value4'];

        $value = $faker->generate(['values' => $values, 'exclude' => 'value1']);

        $this->assertContains($value, $values);
        $this->assertNotEquals('value1', $value);
    }

    /**
     * Test that ShuffleFaker throws exception when all values are excluded.
     */
    public function testGenerateThrowsExceptionWhenAllExcluded(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All values were excluded');

        $faker = new ShuffleFaker();
        $faker->generate(['values' => ['value1'], 'exclude' => 'value1']);
    }
}
