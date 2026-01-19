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
        $faker = new PhoneFaker('en_US');
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
}
