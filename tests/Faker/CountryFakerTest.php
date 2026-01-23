<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\CountryFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for CountryFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class CountryFakerTest extends TestCase
{
    /**
     * Test that CountryFaker generates a valid country code.
     */
    public function testGenerate(): void
    {
        $faker = new CountryFaker('en_US');
        $country = $faker->generate();

        $this->assertIsString($country);
        $this->assertNotEmpty($country);
    }

    /**
     * Test that CountryFaker generates country name format.
     */
    public function testGenerateName(): void
    {
        $faker = new CountryFaker('en_US');
        $country = $faker->generate(['format' => 'name']);

        $this->assertIsString($country);
        $this->assertNotEmpty($country);
    }

    /**
     * Test that CountryFaker generates ISO2 code format.
     */
    public function testGenerateIso2(): void
    {
        $faker = new CountryFaker('en_US');
        $country = $faker->generate(['format' => 'iso2']);

        $this->assertIsString($country);
        $this->assertNotEmpty($country);
        $this->assertEquals(2, strlen($country));
    }

    /**
     * Test that CountryFaker generates ISO3 code format.
     */
    public function testGenerateIso3(): void
    {
        $faker = new CountryFaker('en_US');
        $country = $faker->generate(['format' => 'iso3']);

        $this->assertIsString($country);
        $this->assertNotEmpty($country);
        $this->assertEquals(3, strlen($country));
    }

    /**
     * Test that CountryFaker handles default format (code).
     */
    public function testGenerateWithCodeFormat(): void
    {
        $faker = new CountryFaker('en_US');
        $country = $faker->generate(['format' => 'code']);

        $this->assertIsString($country);
        $this->assertNotEmpty($country);
        $this->assertEquals(2, strlen($country));
    }

    /**
     * Test that CountryFaker handles unknown format gracefully.
     */
    public function testGenerateWithUnknownFormat(): void
    {
        $faker = new CountryFaker('en_US');
        $country = $faker->generate(['format' => 'unknown']);

        $this->assertIsString($country);
        $this->assertNotEmpty($country);
        // Should default to countryCode
        $this->assertEquals(2, strlen($country));
    }

    /**
     * Test that CountryFaker respects locale option.
     */
    public function testGenerateWithLocale(): void
    {
        $faker = new CountryFaker('en_US');
        $country = $faker->generate(['format' => 'name', 'locale' => 'es_ES']);

        $this->assertIsString($country);
        $this->assertNotEmpty($country);
    }

    /**
     * Test that CountryFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new CountryFaker('en_US');
        $this->assertInstanceOf(CountryFaker::class, $faker);
    }

    /**
     * Test that CountryFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new CountryFaker('es_ES');
        $country = $faker->generate();

        $this->assertIsString($country);
        $this->assertNotEmpty($country);
    }

    /**
     * Test that CountryFaker generates different countries.
     */
    public function testGenerateDifferentCountries(): void
    {
        $faker = new CountryFaker('en_US');
        $countries = [];

        for ($i = 0; $i < 10; $i++) {
            $countries[] = $faker->generate();
        }

        // Should have some variation
        $uniqueCountries = array_unique($countries);
        $this->assertGreaterThan(1, count($uniqueCountries));
    }
}
