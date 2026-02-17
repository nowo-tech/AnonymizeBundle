<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\SurnameFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for SurnameFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class SurnameFakerTest extends TestCase
{
    /**
     * Test that SurnameFaker generates a valid surname.
     */
    public function testGenerate(): void
    {
        $faker   = new SurnameFaker('en_US');
        $surname = $faker->generate();

        $this->assertIsString($surname);
        $this->assertNotEmpty($surname);
    }

    /**
     * Test that SurnameFaker generates different surnames.
     */
    public function testGenerateUnique(): void
    {
        $faker    = new SurnameFaker('en_US');
        $surname1 = $faker->generate();
        $surname2 = $faker->generate();

        $this->assertIsString($surname1);
        $this->assertIsString($surname2);
    }

    /**
     * Test that SurnameFaker respects gender option (for API consistency).
     */
    public function testGenerateWithGender(): void
    {
        $faker   = new SurnameFaker('en_US');
        $surname = $faker->generate(['gender' => 'male']);

        $this->assertIsString($surname);
        $this->assertNotEmpty($surname);
    }

    /**
     * Test that SurnameFaker respects gender option (female).
     */
    public function testGenerateWithGenderFemale(): void
    {
        $faker   = new SurnameFaker('en_US');
        $surname = $faker->generate(['gender' => 'female']);

        $this->assertIsString($surname);
        $this->assertNotEmpty($surname);
    }

    /**
     * Test that SurnameFaker respects gender option (random).
     */
    public function testGenerateWithGenderRandom(): void
    {
        $faker   = new SurnameFaker('en_US');
        $surname = $faker->generate(['gender' => 'random']);

        $this->assertIsString($surname);
        $this->assertNotEmpty($surname);
    }

    /**
     * Test that SurnameFaker respects locale_specific option.
     */
    public function testGenerateWithLocaleSpecific(): void
    {
        $faker   = new SurnameFaker('en_US');
        $surname = $faker->generate(['locale_specific' => true]);

        $this->assertIsString($surname);
        $this->assertNotEmpty($surname);
    }

    /**
     * Test that SurnameFaker respects locale_specific false option.
     */
    public function testGenerateWithLocaleSpecificFalse(): void
    {
        $faker   = new SurnameFaker('en_US');
        $surname = $faker->generate(['locale_specific' => false]);

        $this->assertIsString($surname);
        $this->assertNotEmpty($surname);
    }

    /**
     * Test that SurnameFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new SurnameFaker('en_US');
        $this->assertInstanceOf(SurnameFaker::class, $faker);
    }

    /**
     * Test that SurnameFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker   = new SurnameFaker('es_ES');
        $surname = $faker->generate();

        $this->assertIsString($surname);
        $this->assertNotEmpty($surname);
    }
}
