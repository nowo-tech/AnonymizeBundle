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
}
