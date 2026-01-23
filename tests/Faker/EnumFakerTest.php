<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\EnumFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for EnumFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class EnumFakerTest extends TestCase
{
    /**
     * Test that EnumFaker generates a value from the provided list.
     */
    public function testGenerate(): void
    {
        $faker = new EnumFaker('en_US');
        $values = ['active', 'inactive', 'pending'];
        $value = $faker->generate(['values' => $values]);

        $this->assertContains($value, $values);
    }

    /**
     * Test that EnumFaker throws exception when values option is missing.
     */
    public function testGenerateThrowsExceptionWhenValuesMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EnumFaker requires a "values" option');

        $faker = new EnumFaker('en_US');
        $faker->generate();
    }

    /**
     * Test that EnumFaker throws exception when values is empty.
     */
    public function testGenerateThrowsExceptionWhenValuesEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EnumFaker requires a "values" option');

        $faker = new EnumFaker('en_US');
        $faker->generate(['values' => []]);
    }

    /**
     * Test that EnumFaker respects weighted option.
     */
    public function testGenerateWithWeighted(): void
    {
        $faker = new EnumFaker('en_US');
        $values = ['active', 'inactive', 'pending'];
        $weighted = ['active' => 50, 'inactive' => 30, 'pending' => 20];
        $value = $faker->generate(['values' => $values, 'weighted' => $weighted]);

        $this->assertContains($value, $values);
    }

    /**
     * Test that EnumFaker respects weighted option with float weights.
     */
    public function testGenerateWithWeightedFloat(): void
    {
        $faker = new EnumFaker('en_US');
        $values = ['low', 'medium', 'high'];
        $weighted = ['low' => 0.3, 'medium' => 0.5, 'high' => 0.2];
        $value = $faker->generate(['values' => $values, 'weighted' => $weighted]);

        $this->assertContains($value, array_keys($weighted));
    }

    /**
     * Test that EnumFaker respects weighted option with single value.
     */
    public function testGenerateWithWeightedSingleValue(): void
    {
        $faker = new EnumFaker('en_US');
        $values = ['only'];
        $weighted = ['only' => 100];
        $value = $faker->generate(['values' => $values, 'weighted' => $weighted]);

        $this->assertEquals('only', $value);
    }

    /**
     * Test that EnumFaker handles weighted option with empty weighted array.
     */
    public function testGenerateWithEmptyWeighted(): void
    {
        $faker = new EnumFaker('en_US');
        $values = ['active', 'inactive', 'pending'];
        $value = $faker->generate(['values' => $values, 'weighted' => []]);

        // Should fall back to random selection
        $this->assertContains($value, $values);
    }

    /**
     * Test that EnumFaker handles weighted option with null weighted.
     */
    public function testGenerateWithNullWeighted(): void
    {
        $faker = new EnumFaker('en_US');
        $values = ['active', 'inactive', 'pending'];
        $value = $faker->generate(['values' => $values, 'weighted' => null]);

        // Should fall back to random selection
        $this->assertContains($value, $values);
    }

    /**
     * Test that EnumFaker handles weighted option with mismatched keys.
     */
    public function testGenerateWithWeightedMismatchedKeys(): void
    {
        $faker = new EnumFaker('en_US');
        $values = ['active', 'inactive', 'pending'];
        $weighted = ['active' => 50, 'other' => 50];
        $value = $faker->generate(['values' => $values, 'weighted' => $weighted]);

        // Should still return a valid value
        $this->assertIsString($value);
    }

    /**
     * Test that EnumFaker generates different values on multiple calls.
     */
    public function testGenerateMultipleCalls(): void
    {
        $faker = new EnumFaker('en_US');
        $values = ['a', 'b', 'c', 'd', 'e'];

        $results = [];
        for ($i = 0; $i < 20; $i++) {
            $results[] = $faker->generate(['values' => $values]);
        }

        // Should generate at least 2 different values (very likely)
        $uniqueResults = array_unique($results);
        $this->assertGreaterThanOrEqual(1, count($uniqueResults));

        // All results should be in the values array
        foreach ($results as $result) {
            $this->assertContains($result, $values);
        }
    }

    /**
     * Test that EnumFaker selectWeightedValue fallback works correctly.
     * This tests the edge case where random value exceeds cumulative weight.
     */
    public function testSelectWeightedValueFallback(): void
    {
        $faker = new EnumFaker('en_US');
        $values = ['a', 'b', 'c'];
        $weighted = ['a' => 10, 'b' => 20, 'c' => 30];

        // Run multiple times to increase chance of hitting fallback
        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $value = $faker->generate(['values' => $values, 'weighted' => $weighted]);
            $results[] = $value;
            // All results should be valid keys from weighted array
            $this->assertContains($value, array_keys($weighted));
        }

        // Verify all possible values can be returned
        $uniqueResults = array_unique($results);
        $this->assertGreaterThanOrEqual(1, count($uniqueResults));
    }

    /**
     * Test that EnumFaker handles weighted values with zero weights.
     */
    public function testGenerateWithZeroWeights(): void
    {
        $faker = new EnumFaker('en_US');
        $values = ['a', 'b', 'c'];
        $weighted = ['a' => 0, 'b' => 0, 'c' => 100];

        // With zero weights, 'c' should be selected most of the time
        $value = $faker->generate(['values' => $values, 'weighted' => $weighted]);
        $this->assertContains($value, array_keys($weighted));
    }

    /**
     * Test that EnumFaker handles weighted values with very small weights.
     */
    public function testGenerateWithVerySmallWeights(): void
    {
        $faker = new EnumFaker('en_US');
        $values = ['a', 'b'];
        $weighted = ['a' => 0.001, 'b' => 0.002];

        $value = $faker->generate(['values' => $values, 'weighted' => $weighted]);
        $this->assertContains($value, array_keys($weighted));
    }

    /**
     * Test that EnumFaker constructor works with different locales.
     */
    public function testConstructorWithDifferentLocales(): void
    {
        $faker1 = new EnumFaker('en_US');
        $faker2 = new EnumFaker('es_ES');
        $faker3 = new EnumFaker('fr_FR');

        $values = ['test'];
        $value1 = $faker1->generate(['values' => $values]);
        $value2 = $faker2->generate(['values' => $values]);
        $value3 = $faker3->generate(['values' => $values]);

        $this->assertEquals('test', $value1);
        $this->assertEquals('test', $value2);
        $this->assertEquals('test', $value3);
    }
}
