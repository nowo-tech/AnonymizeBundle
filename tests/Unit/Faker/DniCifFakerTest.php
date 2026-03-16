<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Faker;

use Nowo\AnonymizeBundle\Faker\DniCifFaker;
use PHPUnit\Framework\TestCase;

use function strlen;

/**
 * Test case for DniCifFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class DniCifFakerTest extends TestCase
{
    /**
     * Test that DniCifFaker generates valid DNI format.
     */
    public function testGenerateDni(): void
    {
        $faker = new DniCifFaker('es_ES');
        $dni   = $faker->generate(['type' => 'dni']);

        $this->assertIsString($dni);
        $this->assertEquals(9, strlen($dni));
        $this->assertMatchesRegularExpression('/^\d{8}[A-Z]$/', $dni);
    }

    /**
     * Test that DniCifFaker generates valid CIF format.
     */
    public function testGenerateCif(): void
    {
        $faker = new DniCifFaker('es_ES');
        $cif   = $faker->generate(['type' => 'cif']);

        $this->assertIsString($cif);
        $this->assertEquals(9, strlen($cif));
        $this->assertMatchesRegularExpression('/^[A-Z]\d{7}[A-Z0-9]$/', $cif);
    }

    /**
     * Test that DniCifFaker generates valid NIF format (same as DNI).
     */
    public function testGenerateNif(): void
    {
        $faker = new DniCifFaker('es_ES');
        $nif   = $faker->generate(['type' => 'nif']);

        $this->assertIsString($nif);
        $this->assertEquals(9, strlen($nif));
        $this->assertMatchesRegularExpression('/^\d{8}[A-Z]$/', $nif);
    }

    /**
     * Test that DniCifFaker auto-detects DNI type.
     */
    public function testGenerateAutoDetectsDni(): void
    {
        $faker = new DniCifFaker('es_ES');
        $dni   = $faker->generate(['type' => 'auto', 'original_value' => '12345678A']);

        $this->assertIsString($dni);
        $this->assertEquals(9, strlen($dni));
        $this->assertMatchesRegularExpression('/^\d{8}[A-Z]$/', $dni);
    }

    /**
     * Test that DniCifFaker auto-detects CIF type.
     */
    public function testGenerateAutoDetectsCif(): void
    {
        $faker = new DniCifFaker('es_ES');
        $cif   = $faker->generate(['type' => 'auto', 'original_value' => 'A12345674']);

        $this->assertIsString($cif);
        $this->assertEquals(9, strlen($cif));
        $this->assertMatchesRegularExpression('/^[A-Z]\d{7}[A-Z0-9]$/', $cif);
    }

    /**
     * Test that DniCifFaker formats DNI with separator.
     */
    public function testGenerateDniFormatted(): void
    {
        $faker = new DniCifFaker('es_ES');
        $dni   = $faker->generate(['type' => 'dni', 'formatted' => true]);

        $this->assertIsString($dni);
        $this->assertEquals(10, strlen($dni)); // 8 digits + 1 dash + 1 letter
        $this->assertMatchesRegularExpression('/^\d{8}-[A-Z]$/', $dni);
    }

    /**
     * Test that DniCifFaker formats CIF with separator.
     */
    public function testGenerateCifFormatted(): void
    {
        $faker = new DniCifFaker('es_ES');
        $cif   = $faker->generate(['type' => 'cif', 'formatted' => true]);

        $this->assertIsString($cif);
        $this->assertEquals(11, strlen($cif)); // 1 letter + 1 dash + 7 digits + 1 dash + 1 letter/digit
        $this->assertMatchesRegularExpression('/^[A-Z]-\d{7}-[A-Z0-9]$/', $cif);
    }

    /**
     * Test that DniCifFaker defaults to DNI when auto-detection fails.
     */
    public function testGenerateAutoDefaultsToDni(): void
    {
        $faker = new DniCifFaker('es_ES');
        $dni   = $faker->generate(['type' => 'auto']);

        $this->assertIsString($dni);
        $this->assertEquals(9, strlen($dni));
        $this->assertMatchesRegularExpression('/^\d{8}[A-Z]$/', $dni);
    }

    /**
     * Test that DniCifFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new DniCifFaker('es_ES');
        $this->assertInstanceOf(DniCifFaker::class, $faker);
    }

    /**
     * Test that DniCifFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new DniCifFaker('en_US');
        $dni   = $faker->generate(['type' => 'dni']);

        $this->assertIsString($dni);
        $this->assertEquals(9, strlen($dni));
        $this->assertMatchesRegularExpression('/^\d{8}[A-Z]$/', $dni);
    }

    /**
     * Test that DniCifFaker auto-detection defaults to DNI when value does not match CIF or DNI pattern.
     */
    public function testGenerateAutoDetectsDefaultsToDniWhenValueUnrecognized(): void
    {
        $faker = new DniCifFaker('es_ES');
        $result = $faker->generate(['type' => 'auto', 'original_value' => 'X']);

        $this->assertIsString($result);
        $this->assertEquals(9, strlen($result));
        $this->assertMatchesRegularExpression('/^\d{8}[A-Z]$/', $result);
    }

    /**
     * Test that DniCifFaker with unknown type defaults to DNI.
     */
    public function testGenerateWithUnknownTypeDefaultsToDni(): void
    {
        $faker = new DniCifFaker('es_ES');
        $result = $faker->generate(['type' => 'other']);

        $this->assertIsString($result);
        $this->assertEquals(9, strlen($result));
        $this->assertMatchesRegularExpression('/^\d{8}[A-Z]$/', $result);
    }

    /**
     * Test that DniCifFaker auto-detects DNI from formatted value (with spaces/dash).
     */
    public function testGenerateAutoDetectsDniFromFormattedValue(): void
    {
        $faker = new DniCifFaker('es_ES');
        $dni   = $faker->generate(['type' => 'auto', 'original_value' => '12345678-A']);

        $this->assertIsString($dni);
        $this->assertEquals(9, strlen($dni));
        $this->assertMatchesRegularExpression('/^\d{8}[A-Z]$/', $dni);
    }

    /**
     * Test that DniCifFaker auto-detects type from value with spaces and dots removed.
     */
    public function testGenerateAutoDetectsCifFromFormattedValue(): void
    {
        $faker = new DniCifFaker('es_ES');
        $cif   = $faker->generate(['type' => 'auto', 'original_value' => 'A 1234567 4']);

        $this->assertIsString($cif);
        $this->assertEquals(9, strlen($cif));
        $this->assertMatchesRegularExpression('/^[A-Z]\d{7}[A-Z0-9]$/', $cif);
    }

    /**
     * Test that DniCifFaker formats NIF with separator (same as DNI: 12345678-A).
     */
    public function testGenerateNifFormatted(): void
    {
        $faker = new DniCifFaker('es_ES');
        $nif   = $faker->generate(['type' => 'nif', 'formatted' => true]);

        $this->assertIsString($nif);
        $this->assertEquals(10, strlen($nif));
        $this->assertMatchesRegularExpression('/^\d{8}-[A-Z]$/', $nif);
    }

    /**
     * Test that DniCifFaker with type auto and non-string original_value defaults to DNI (skips detectType).
     */
    public function testGenerateAutoWithNonStringOriginalValueDefaultsToDni(): void
    {
        $faker  = new DniCifFaker('es_ES');
        $result = $faker->generate(['type' => 'auto', 'original_value' => 12345]);

        $this->assertIsString($result);
        $this->assertEquals(9, strlen($result));
        $this->assertMatchesRegularExpression('/^\d{8}[A-Z]$/', $result);
    }
}
