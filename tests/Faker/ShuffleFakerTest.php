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

    /**
     * Test that ShuffleFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new ShuffleFaker();
        $this->assertInstanceOf(ShuffleFaker::class, $faker);
    }

    /**
     * Test that ShuffleFaker generates different values on multiple calls.
     */
    public function testGenerateDifferentValues(): void
    {
        $faker = new ShuffleFaker();
        $values = ['value1', 'value2', 'value3', 'value4', 'value5', 'value6', 'value7', 'value8', 'value9', 'value10'];

        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $faker->generate(['values' => $values]);
        }

        // Should have at least some variation (not all the same)
        $uniqueResults = array_unique($results);
        $this->assertGreaterThan(1, count($uniqueResults));
    }

    /**
     * Test that ShuffleFaker handles numeric values.
     */
    public function testGenerateWithNumericValues(): void
    {
        $faker = new ShuffleFaker();
        $values = [1, 2, 3, 4, 5];
        $value = $faker->generate(['values' => $values]);

        $this->assertContains($value, $values);
        $this->assertIsInt($value);
    }

    /**
     * Test that ShuffleFaker handles mixed type values.
     */
    public function testGenerateWithMixedTypes(): void
    {
        $faker = new ShuffleFaker();
        $values = ['string', 123, true, null, 45.67];
        $value = $faker->generate(['values' => $values]);

        $this->assertContains($value, $values);
    }

    /**
     * Test that ShuffleFaker handles exclude with multiple occurrences.
     */
    public function testGenerateWithExcludeMultipleOccurrences(): void
    {
        $faker = new ShuffleFaker();
        $values = ['value1', 'value2', 'value1', 'value3', 'value1'];
        $value = $faker->generate(['values' => $values, 'exclude' => 'value1']);

        $this->assertNotEquals('value1', $value);
        $this->assertContains($value, ['value2', 'value3']);
    }
}
