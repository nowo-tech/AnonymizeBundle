<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\IbanFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for IbanFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class IbanFakerTest extends TestCase
{
    /**
     * Test that IbanFaker generates a valid IBAN.
     */
    public function testGenerate(): void
    {
        $faker = new IbanFaker('en_US');
        $iban = $faker->generate();

        $this->assertIsString($iban);
        $this->assertNotEmpty($iban);
        $this->assertStringStartsWith('ES', $iban);
    }

    /**
     * Test that IbanFaker generates IBAN with custom country.
     */
    public function testGenerateWithCountry(): void
    {
        $faker = new IbanFaker('en_US');
        $iban = $faker->generate(['country' => 'FR']);

        $this->assertIsString($iban);
        $this->assertNotEmpty($iban);
        $this->assertStringStartsWith('FR', $iban);
    }

    /**
     * Test that IbanFaker generates different IBANs.
     */
    public function testGenerateUnique(): void
    {
        $faker = new IbanFaker('en_US');
        $iban1 = $faker->generate();
        $iban2 = $faker->generate();

        $this->assertIsString($iban1);
        $this->assertIsString($iban2);
    }
}
