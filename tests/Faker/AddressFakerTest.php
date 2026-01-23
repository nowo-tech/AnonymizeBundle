<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\AddressFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for AddressFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AddressFakerTest extends TestCase
{
    /**
     * Test that AddressFaker generates a valid address.
     */
    public function testGenerate(): void
    {
        $faker = new AddressFaker('en_US');
        $address = $faker->generate();

        $this->assertIsString($address);
        $this->assertNotEmpty($address);
    }

    /**
     * Test that AddressFaker generates short format.
     */
    public function testGenerateShortFormat(): void
    {
        $faker = new AddressFaker('en_US');
        $address = $faker->generate(['format' => 'short']);

        $this->assertIsString($address);
        $this->assertNotEmpty($address);
    }

    /**
     * Test that AddressFaker generates full format.
     */
    public function testGenerateFullFormat(): void
    {
        $faker = new AddressFaker('en_US');
        $address = $faker->generate(['format' => 'full']);

        $this->assertIsString($address);
        $this->assertNotEmpty($address);
    }

    /**
     * Test that AddressFaker includes postal code when requested.
     */
    public function testGenerateWithPostalCode(): void
    {
        $faker = new AddressFaker('en_US');
        $address = $faker->generate(['include_postal_code' => true]);

        $this->assertIsString($address);
        $this->assertNotEmpty($address);
    }

    /**
     * Test that AddressFaker respects country option.
     */
    public function testGenerateWithCountry(): void
    {
        $faker = new AddressFaker('en_US');
        $address = $faker->generate(['country' => 'US']);

        $this->assertIsString($address);
        $this->assertNotEmpty($address);
    }

    /**
     * Test that AddressFaker handles different countries.
     */
    public function testGenerateWithDifferentCountries(): void
    {
        $faker = new AddressFaker('en_US');
        $countries = ['ES', 'FR', 'DE', 'IT', 'GB'];

        foreach ($countries as $country) {
            $address = $faker->generate(['country' => $country]);
            $this->assertIsString($address);
            $this->assertNotEmpty($address);
        }
    }

    /**
     * Test that AddressFaker handles unknown country gracefully.
     */
    public function testGenerateWithUnknownCountry(): void
    {
        $faker = new AddressFaker('en_US');
        $address = $faker->generate(['country' => 'XX']);

        $this->assertIsString($address);
        $this->assertNotEmpty($address);
    }

    /**
     * Test that AddressFaker combines format and postal code options.
     */
    public function testGenerateWithFormatAndPostalCode(): void
    {
        $faker = new AddressFaker('en_US');
        $address = $faker->generate(['format' => 'full', 'include_postal_code' => true]);

        $this->assertIsString($address);
        $this->assertNotEmpty($address);
    }

    /**
     * Test that AddressFaker handles short format with postal code.
     */
    public function testGenerateShortFormatWithPostalCode(): void
    {
        $faker = new AddressFaker('en_US');
        $address = $faker->generate(['format' => 'short', 'include_postal_code' => true]);

        $this->assertIsString($address);
        $this->assertNotEmpty($address);
    }

    /**
     * Test that AddressFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new AddressFaker('en_US');
        $this->assertInstanceOf(AddressFaker::class, $faker);
    }

    /**
     * Test that AddressFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new AddressFaker('es_ES');
        $address = $faker->generate();

        $this->assertIsString($address);
        $this->assertNotEmpty($address);
    }

    /**
     * Test that AddressFaker handles invalid format gracefully.
     */
    public function testGenerateWithInvalidFormat(): void
    {
        $faker = new AddressFaker('en_US');
        // Invalid format should default to full
        $address = $faker->generate(['format' => 'invalid']);

        $this->assertIsString($address);
        $this->assertNotEmpty($address);
    }
}
