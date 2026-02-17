<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\NameFallbackFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for NameFallbackFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class NameFallbackFakerTest extends TestCase
{
    /**
     * Test that NameFallbackFaker generates a name when current field is null but related field has value.
     */
    public function testGenerateWhenCurrentNullButRelatedHasValue(): void
    {
        $faker = new NameFallbackFaker('en_US');
        $name  = $faker->generate([
            'original_value' => null,
            'fallback_field' => 'firstname',
            'record'         => ['firstname' => 'John'],
        ]);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFallbackFaker generates a name when current field has value.
     */
    public function testGenerateWhenCurrentHasValue(): void
    {
        $faker = new NameFallbackFaker('en_US');
        $name  = $faker->generate([
            'original_value' => 'John',
            'fallback_field' => 'firstname',
            'record'         => ['firstname' => null],
        ]);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
        $this->assertNotEquals('John', $name); // Should be anonymized
    }

    /**
     * Test that NameFallbackFaker generates a name when both fields are null.
     */
    public function testGenerateWhenBothNull(): void
    {
        $faker = new NameFallbackFaker('en_US');
        $name  = $faker->generate([
            'original_value' => null,
            'fallback_field' => 'firstname',
            'record'         => ['firstname' => null],
        ]);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFallbackFaker generates a name when both fields have values.
     */
    public function testGenerateWhenBothHaveValues(): void
    {
        $faker = new NameFallbackFaker('en_US');
        $name  = $faker->generate([
            'original_value' => 'John',
            'fallback_field' => 'firstname',
            'record'         => ['firstname' => 'Jane'],
        ]);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
        $this->assertNotEquals('John', $name); // Should be anonymized
    }

    /**
     * Test that NameFallbackFaker works without fallback_field.
     */
    public function testGenerateWithoutFallbackField(): void
    {
        $faker = new NameFallbackFaker('en_US');
        $name  = $faker->generate([
            'original_value' => 'John',
        ]);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFallbackFaker respects gender option.
     */
    public function testGenerateWithGender(): void
    {
        $faker = new NameFallbackFaker('en_US');
        $name  = $faker->generate([
            'original_value' => 'John',
            'gender'         => 'male',
        ]);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFallbackFaker handles empty string as null.
     */
    public function testGenerateWithEmptyString(): void
    {
        $faker = new NameFallbackFaker('en_US');
        $name  = $faker->generate([
            'original_value' => '',
            'fallback_field' => 'firstname',
            'record'         => ['firstname' => 'John'],
        ]);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFallbackFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new NameFallbackFaker('en_US');
        $this->assertInstanceOf(NameFallbackFaker::class, $faker);
    }

    /**
     * Test that NameFallbackFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new NameFallbackFaker('es_ES');
        $name  = $faker->generate([
            'original_value' => 'Juan',
        ]);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFallbackFaker handles different field name variations.
     */
    public function testGenerateWithDifferentFieldNameVariations(): void
    {
        $faker = new NameFallbackFaker('en_US');

        // Test with lowercase field name
        $name1 = $faker->generate([
            'original_value' => null,
            'fallback_field' => 'firstname',
            'record'         => ['firstname' => 'John'],
        ]);
        $this->assertIsString($name1);
        $this->assertNotEmpty($name1);

        // Test with uppercase field name
        $name2 = $faker->generate([
            'original_value' => null,
            'fallback_field' => 'FirstName',
            'record'         => ['FirstName' => 'John'],
        ]);
        $this->assertIsString($name2);
        $this->assertNotEmpty($name2);
    }
}
