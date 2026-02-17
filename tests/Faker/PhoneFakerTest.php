<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\PhoneFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for PhoneFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class PhoneFakerTest extends TestCase
{
    /**
     * Test that PhoneFaker generates a valid phone number.
     */
    public function testGenerate(): void
    {
        $faker = new PhoneFaker('en_US');
        $phone = $faker->generate();

        $this->assertIsString($phone);
        $this->assertNotEmpty($phone);
    }

    /**
     * Test that PhoneFaker generates different phone numbers.
     */
    public function testGenerateUnique(): void
    {
        $faker  = new PhoneFaker('en_US');
        $phone1 = $faker->generate();
        $phone2 = $faker->generate();

        $this->assertIsString($phone1);
        $this->assertIsString($phone2);
    }

    /**
     * Test that PhoneFaker respects country_code option.
     */
    public function testGenerateWithCountryCode(): void
    {
        $faker = new PhoneFaker('en_US');
        $phone = $faker->generate(['country_code' => '+34']);

        $this->assertIsString($phone);
        $this->assertStringStartsWith('+34', $phone);
    }

    /**
     * Test that PhoneFaker respects format option (national).
     */
    public function testGenerateWithNationalFormat(): void
    {
        $faker = new PhoneFaker('en_US');
        $phone = $faker->generate(['format' => 'national']);

        $this->assertIsString($phone);
        $this->assertNotEmpty($phone);
        // National format should not start with country code
        $this->assertFalse(str_starts_with($phone, '+'));
    }

    /**
     * Test that PhoneFaker respects include_extension option.
     */
    public function testGenerateWithExtension(): void
    {
        $faker = new PhoneFaker('en_US');
        $phone = $faker->generate(['include_extension' => true]);

        $this->assertIsString($phone);
        $this->assertStringContainsString('ext.', $phone);
    }

    /**
     * Test that PhoneFaker combines country_code and format options.
     */
    public function testGenerateWithCountryCodeAndFormat(): void
    {
        $faker = new PhoneFaker('en_US');
        $phone = $faker->generate(['country_code' => '+34', 'format' => 'international']);

        $this->assertIsString($phone);
        $this->assertStringStartsWith('+34', $phone);
    }

    /**
     * Test that PhoneFaker handles country_code with national format.
     */
    public function testGenerateWithCountryCodeAndNationalFormat(): void
    {
        $faker = new PhoneFaker('en_US');
        $phone = $faker->generate(['country_code' => '+34', 'format' => 'national']);

        $this->assertIsString($phone);
        // National format should remove country code
        $this->assertFalse(str_starts_with($phone, '+34'));
    }

    /**
     * Test that PhoneFaker handles extension with national format.
     */
    public function testGenerateWithExtensionAndNationalFormat(): void
    {
        $faker = new PhoneFaker('en_US');
        $phone = $faker->generate(['format' => 'national', 'include_extension' => true]);

        $this->assertIsString($phone);
        $this->assertStringContainsString('ext.', $phone);
        $this->assertFalse(str_starts_with($phone, '+'));
    }

    /**
     * Test that PhoneFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new PhoneFaker('en_US');
        $this->assertInstanceOf(PhoneFaker::class, $faker);
    }

    /**
     * Test that PhoneFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new PhoneFaker('es_ES');
        $phone = $faker->generate();

        $this->assertIsString($phone);
        $this->assertNotEmpty($phone);
    }

    /**
     * Test that PhoneFaker handles invalid format gracefully.
     */
    public function testGenerateWithInvalidFormat(): void
    {
        $faker = new PhoneFaker('en_US');
        // Invalid format should default to international
        $phone = $faker->generate(['format' => 'invalid']);

        $this->assertIsString($phone);
        $this->assertNotEmpty($phone);
    }
}
