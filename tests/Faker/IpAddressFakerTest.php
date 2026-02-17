<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\IpAddressFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for IpAddressFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class IpAddressFakerTest extends TestCase
{
    /**
     * Test that IpAddressFaker generates a valid IPv4 address.
     */
    public function testGenerateIpv4(): void
    {
        $faker = new IpAddressFaker('en_US');
        $ip    = $faker->generate();

        $this->assertIsString($ip);
        $this->assertMatchesRegularExpression('/^(\d{1,3}\.){3}\d{1,3}$/', $ip);
    }

    /**
     * Test that IpAddressFaker generates IPv4 with version option.
     */
    public function testGenerateIpv4WithVersion(): void
    {
        $faker = new IpAddressFaker('en_US');
        $ip    = $faker->generate(['version' => 4]);

        $this->assertIsString($ip);
        $this->assertMatchesRegularExpression('/^(\d{1,3}\.){3}\d{1,3}$/', $ip);
    }

    /**
     * Test that IpAddressFaker generates IPv6 address.
     */
    public function testGenerateIpv6(): void
    {
        $faker = new IpAddressFaker('en_US');
        $ip    = $faker->generate(['version' => 6]);

        $this->assertIsString($ip);
        $this->assertMatchesRegularExpression('/^([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$/', $ip);
    }

    /**
     * Test that IpAddressFaker generates localhost IPv6.
     */
    public function testGenerateIpv6Localhost(): void
    {
        $faker = new IpAddressFaker('en_US');
        $ip    = $faker->generate(['version' => 6, 'type' => 'localhost']);

        $this->assertEquals('::1', $ip);
    }

    /**
     * Test that IpAddressFaker generates private IPv6.
     */
    public function testGenerateIpv6Private(): void
    {
        $faker = new IpAddressFaker('en_US');
        $ip    = $faker->generate(['version' => 6, 'type' => 'private']);

        $this->assertIsString($ip);
        $this->assertStringStartsWith('fe80:', $ip);
    }

    /**
     * Test that IpAddressFaker generates private IPv4.
     */
    public function testGenerateIpv4Private(): void
    {
        $faker = new IpAddressFaker('en_US');
        $ip    = $faker->generate(['version' => 4, 'type' => 'private']);

        $this->assertIsString($ip);
        $this->assertMatchesRegularExpression('/^(\d{1,3}\.){3}\d{1,3}$/', $ip);
    }

    /**
     * Test that IpAddressFaker generates localhost IPv4.
     */
    public function testGenerateIpv4Localhost(): void
    {
        $faker = new IpAddressFaker('en_US');
        $ip    = $faker->generate(['version' => 4, 'type' => 'localhost']);

        $this->assertIsString($ip);
        $this->assertMatchesRegularExpression('/^(\d{1,3}\.){3}\d{1,3}$/', $ip);
    }

    /**
     * Test that IpAddressFaker generates public IPv4 by default.
     */
    public function testGenerateIpv4Public(): void
    {
        $faker = new IpAddressFaker('en_US');
        $ip    = $faker->generate(['version' => 4, 'type' => 'public']);

        $this->assertIsString($ip);
        $this->assertMatchesRegularExpression('/^(\d{1,3}\.){3}\d{1,3}$/', $ip);
    }

    /**
     * Test that IpAddressFaker generates public IPv6 by default.
     */
    public function testGenerateIpv6Public(): void
    {
        $faker = new IpAddressFaker('en_US');
        $ip    = $faker->generate(['version' => 6, 'type' => 'public']);

        $this->assertIsString($ip);
        $this->assertMatchesRegularExpression('/^([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$/', $ip);
    }

    /**
     * Test that IpAddressFaker handles invalid version gracefully.
     */
    public function testGenerateWithInvalidVersion(): void
    {
        $faker = new IpAddressFaker('en_US');
        // Invalid version should default to IPv4
        $ip = $faker->generate(['version' => 99]);

        $this->assertIsString($ip);
        $this->assertMatchesRegularExpression('/^(\d{1,3}\.){3}\d{1,3}$/', $ip);
    }

    /**
     * Test that IpAddressFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new IpAddressFaker('en_US');
        $this->assertInstanceOf(IpAddressFaker::class, $faker);
    }
}
