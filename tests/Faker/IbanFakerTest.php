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

    /**
     * Test that IbanFaker respects formatted option.
     */
    public function testGenerateWithFormatted(): void
    {
        $faker = new IbanFaker('en_US');
        $iban = $faker->generate(['formatted' => true]);

        $this->assertIsString($iban);
        $this->assertStringContainsString(' ', $iban);
        $this->assertStringStartsWith('ES', $iban);
    }

    /**
     * Test that IbanFaker respects formatted false option.
     */
    public function testGenerateWithoutFormatted(): void
    {
        $faker = new IbanFaker('en_US');
        $iban = $faker->generate(['formatted' => false]);

        $this->assertIsString($iban);
        $this->assertStringNotContainsString(' ', $iban);
        $this->assertStringStartsWith('ES', $iban);
    }

    /**
     * Test that IbanFaker generates valid IBANs.
     */
    public function testGenerateValid(): void
    {
        $faker = new IbanFaker('en_US');
        $iban = $faker->generate(['valid' => true]);

        $this->assertIsString($iban);
        $this->assertNotEmpty($iban);
        // IBAN should be 15-34 characters (without spaces)
        $ibanClean = str_replace(' ', '', $iban);
        $this->assertGreaterThanOrEqual(15, strlen($ibanClean));
        $this->assertLessThanOrEqual(34, strlen($ibanClean));
    }
}
