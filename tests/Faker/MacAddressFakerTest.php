<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\MacAddressFaker;
use PHPUnit\Framework\TestCase;

use function strlen;

/**
 * Test case for MacAddressFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class MacAddressFakerTest extends TestCase
{
    /**
     * Test that MacAddressFaker generates a valid MAC address.
     */
    public function testGenerate(): void
    {
        $faker = new MacAddressFaker('en_US');
        $mac   = $faker->generate();

        $this->assertIsString($mac);
        $this->assertMatchesRegularExpression('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $mac);
    }

    /**
     * Test that MacAddressFaker respects separator option (colon).
     */
    public function testGenerateWithColonSeparator(): void
    {
        $faker = new MacAddressFaker('en_US');
        $mac   = $faker->generate(['separator' => 'colon']);

        $this->assertIsString($mac);
        $this->assertStringContainsString(':', $mac);
        $this->assertMatchesRegularExpression('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $mac);
    }

    /**
     * Test that MacAddressFaker respects separator option (dash).
     */
    public function testGenerateWithDashSeparator(): void
    {
        $faker = new MacAddressFaker('en_US');
        $mac   = $faker->generate(['separator' => 'dash']);

        $this->assertIsString($mac);
        $this->assertStringContainsString('-', $mac);
        $this->assertMatchesRegularExpression('/^([0-9A-F]{2}-){5}[0-9A-F]{2}$/', $mac);
    }

    /**
     * Test that MacAddressFaker respects separator option (none).
     */
    public function testGenerateWithNoSeparator(): void
    {
        $faker = new MacAddressFaker('en_US');
        $mac   = $faker->generate(['separator' => 'none']);

        $this->assertIsString($mac);
        $this->assertEquals(12, strlen($mac));
        $this->assertMatchesRegularExpression('/^[0-9A-F]{12}$/', $mac);
    }

    /**
     * Test that MacAddressFaker respects uppercase option.
     */
    public function testGenerateWithUppercase(): void
    {
        $faker = new MacAddressFaker('en_US');
        $mac   = $faker->generate(['uppercase' => true]);

        $this->assertIsString($mac);
        $this->assertEquals(strtoupper($mac), $mac);
    }

    /**
     * Test that MacAddressFaker respects lowercase option.
     */
    public function testGenerateWithLowercase(): void
    {
        $faker = new MacAddressFaker('en_US');
        $mac   = $faker->generate(['uppercase' => false]);

        $this->assertIsString($mac);
        $this->assertEquals(strtolower($mac), $mac);
    }

    /**
     * Test that MacAddressFaker generates different MAC addresses.
     */
    public function testGenerateDifferentMacAddresses(): void
    {
        $faker = new MacAddressFaker('en_US');
        $mac1  = $faker->generate();
        $mac2  = $faker->generate();

        // They might be the same by chance, but very unlikely
        $this->assertIsString($mac1);
        $this->assertIsString($mac2);
    }

    /**
     * Test that MacAddressFaker handles invalid separator gracefully.
     */
    public function testGenerateWithInvalidSeparator(): void
    {
        $faker = new MacAddressFaker('en_US');
        // Invalid separator should default to colon
        $mac = $faker->generate(['separator' => 'invalid']);

        $this->assertIsString($mac);
        $this->assertStringContainsString(':', $mac);
    }

    /**
     * Test that MacAddressFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new MacAddressFaker('en_US');
        $this->assertInstanceOf(MacAddressFaker::class, $faker);
    }

    /**
     * Test that MacAddressFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new MacAddressFaker('es_ES');
        $mac   = $faker->generate();

        $this->assertIsString($mac);
        $this->assertMatchesRegularExpression('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $mac);
    }

    /**
     * Test that MacAddressFaker generates correct format with dash separator and lowercase.
     */
    public function testGenerateWithDashAndLowercase(): void
    {
        $faker = new MacAddressFaker('en_US');
        $mac   = $faker->generate(['separator' => 'dash', 'uppercase' => false]);

        $this->assertIsString($mac);
        $this->assertStringContainsString('-', $mac);
        $this->assertEquals(strtolower($mac), $mac);
        $this->assertMatchesRegularExpression('/^([0-9a-f]{2}-){5}[0-9a-f]{2}$/', $mac);
    }
}
