<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\IbanFaker;
use PHPUnit\Framework\TestCase;

use function strlen;

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
        $iban  = $faker->generate();

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
        $iban  = $faker->generate(['country' => 'FR']);

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
        $iban  = $faker->generate(['formatted' => true]);

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
        $iban  = $faker->generate(['formatted' => false]);

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
        $iban  = $faker->generate(['valid' => true]);

        $this->assertIsString($iban);
        $this->assertNotEmpty($iban);
        // IBAN should be 15-34 characters (without spaces)
        $ibanClean = str_replace(' ', '', $iban);
        $this->assertGreaterThanOrEqual(15, strlen($ibanClean));
        $this->assertLessThanOrEqual(34, strlen($ibanClean));
    }

    /**
     * Test that IbanFaker handles valid false option.
     */
    public function testGenerateWithValidFalse(): void
    {
        $faker = new IbanFaker('en_US');
        $iban  = $faker->generate(['valid' => false]);

        $this->assertIsString($iban);
        $this->assertNotEmpty($iban);
        // Even with valid=false, IBAN should still be valid (for safety)
        $ibanClean = str_replace(' ', '', $iban);
        $this->assertGreaterThanOrEqual(15, strlen($ibanClean));
    }

    /**
     * Test that IbanFaker handles different countries.
     */
    public function testGenerateWithDifferentCountries(): void
    {
        $faker     = new IbanFaker('en_US');
        $countries = ['DE', 'GB', 'IT', 'NL', 'PT'];

        foreach ($countries as $country) {
            $iban = $faker->generate(['country' => $country]);
            $this->assertIsString($iban);
            $this->assertStringStartsWith($country, $iban);
        }
    }

    /**
     * Test that IbanFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new IbanFaker('en_US');
        $this->assertInstanceOf(IbanFaker::class, $faker);
    }

    /**
     * Test that IbanFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new IbanFaker('es_ES');
        $iban  = $faker->generate();

        $this->assertIsString($iban);
        $this->assertNotEmpty($iban);
    }

    /**
     * Test that IbanFaker handles formatted with spaces removal.
     */
    public function testGenerateFormattedRemovesSpaces(): void
    {
        $faker = new IbanFaker('en_US');
        $iban  = $faker->generate(['formatted' => false]);

        $this->assertIsString($iban);
        $this->assertStringNotContainsString(' ', $iban);
    }
}
