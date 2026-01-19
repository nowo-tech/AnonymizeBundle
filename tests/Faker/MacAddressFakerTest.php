<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\MacAddressFaker;
use PHPUnit\Framework\TestCase;

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
        $mac = $faker->generate();

        $this->assertIsString($mac);
        $this->assertMatchesRegularExpression('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $mac);
    }

    /**
     * Test that MacAddressFaker respects separator option (colon).
     */
    public function testGenerateWithColonSeparator(): void
    {
        $faker = new MacAddressFaker('en_US');
        $mac = $faker->generate(['separator' => 'colon']);

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
        $mac = $faker->generate(['separator' => 'dash']);

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
        $mac = $faker->generate(['separator' => 'none']);

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
        $mac = $faker->generate(['uppercase' => true]);

        $this->assertIsString($mac);
        $this->assertEquals(strtoupper($mac), $mac);
    }

    /**
     * Test that MacAddressFaker respects lowercase option.
     */
    public function testGenerateWithLowercase(): void
    {
        $faker = new MacAddressFaker('en_US');
        $mac = $faker->generate(['uppercase' => false]);

        $this->assertIsString($mac);
        $this->assertEquals(strtolower($mac), $mac);
    }
}
