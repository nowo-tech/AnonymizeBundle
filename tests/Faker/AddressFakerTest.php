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
}
