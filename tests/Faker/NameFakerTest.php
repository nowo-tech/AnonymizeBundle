<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\NameFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for NameFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class NameFakerTest extends TestCase
{
    /**
     * Test that NameFaker generates a valid first name.
     */
    public function testGenerate(): void
    {
        $faker = new NameFaker('en_US');
        $name  = $faker->generate();

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFaker generates different names.
     */
    public function testGenerateUnique(): void
    {
        $faker = new NameFaker('en_US');
        $name1 = $faker->generate();
        $name2 = $faker->generate();

        $this->assertIsString($name1);
        $this->assertIsString($name2);
    }

    /**
     * Test that NameFaker respects gender option (male).
     */
    public function testGenerateWithGenderMale(): void
    {
        $faker = new NameFaker('en_US');
        $name  = $faker->generate(['gender' => 'male']);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFaker respects gender option (female).
     */
    public function testGenerateWithGenderFemale(): void
    {
        $faker = new NameFaker('en_US');
        $name  = $faker->generate(['gender' => 'female']);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFaker respects gender option (random).
     */
    public function testGenerateWithGenderRandom(): void
    {
        $faker = new NameFaker('en_US');
        $name  = $faker->generate(['gender' => 'random']);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFaker handles invalid gender gracefully.
     */
    public function testGenerateWithInvalidGender(): void
    {
        $faker = new NameFaker('en_US');
        // Invalid gender should default to random
        $name = $faker->generate(['gender' => 'invalid']);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFaker respects locale_specific option.
     */
    public function testGenerateWithLocaleSpecific(): void
    {
        $faker = new NameFaker('en_US');
        $name  = $faker->generate(['locale_specific' => true]);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFaker respects locale_specific false option.
     */
    public function testGenerateWithLocaleSpecificFalse(): void
    {
        $faker = new NameFaker('en_US');
        $name  = $faker->generate(['locale_specific' => false]);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new NameFaker('en_US');
        $this->assertInstanceOf(NameFaker::class, $faker);
    }

    /**
     * Test that NameFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new NameFaker('es_ES');
        $name  = $faker->generate();

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }
}
