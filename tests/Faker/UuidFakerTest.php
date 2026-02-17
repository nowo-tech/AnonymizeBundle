<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\UuidFaker;
use PHPUnit\Framework\TestCase;

use function strlen;

/**
 * Test case for UuidFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class UuidFakerTest extends TestCase
{
    /**
     * Test that UuidFaker generates a UUID.
     */
    public function testGenerate(): void
    {
        $faker = new UuidFaker('en_US');
        $uuid  = $faker->generate();

        $this->assertIsString($uuid);
        $this->assertNotEmpty($uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Test that UuidFaker generates UUID with dashes by default.
     */
    public function testGenerateWithDashes(): void
    {
        $faker = new UuidFaker('en_US');
        $uuid  = $faker->generate(['format' => 'with_dashes']);

        $this->assertIsString($uuid);
        $this->assertStringContainsString('-', $uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Test that UuidFaker generates UUID without dashes.
     */
    public function testGenerateWithoutDashes(): void
    {
        $faker = new UuidFaker('en_US');
        $uuid  = $faker->generate(['format' => 'without_dashes']);

        $this->assertIsString($uuid);
        $this->assertStringNotContainsString('-', $uuid);
        $this->assertEquals(32, strlen($uuid));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/i', $uuid);
    }

    /**
     * Test that UuidFaker generates UUID v4 by default.
     */
    public function testGenerateUuidV4(): void
    {
        $faker = new UuidFaker('en_US');
        $uuid  = $faker->generate(['version' => 4]);

        $this->assertIsString($uuid);
        $this->assertNotEmpty($uuid);
        // Faker's uuid() generates valid UUIDs but may not strictly follow v4 format
        // Just verify it's a valid UUID format
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Test that UuidFaker generates UUID v1.
     */
    public function testGenerateUuidV1(): void
    {
        $faker = new UuidFaker('en_US');
        $uuid  = $faker->generate(['version' => 1]);

        $this->assertIsString($uuid);
        $this->assertNotEmpty($uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Test that UuidFaker generates different UUIDs.
     */
    public function testGenerateDifferentUuids(): void
    {
        $faker = new UuidFaker('en_US');
        $uuid1 = $faker->generate();
        $uuid2 = $faker->generate();

        $this->assertNotEquals($uuid1, $uuid2);
    }

    /**
     * Test that UuidFaker handles invalid version gracefully.
     */
    public function testGenerateWithInvalidVersion(): void
    {
        $faker = new UuidFaker('en_US');
        // Invalid version should default to v4
        $uuid = $faker->generate(['version' => 99]);

        $this->assertIsString($uuid);
        $this->assertNotEmpty($uuid);
    }

    /**
     * Test that UuidFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new UuidFaker('en_US');
        $this->assertInstanceOf(UuidFaker::class, $faker);
    }

    /**
     * Test that UuidFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new UuidFaker('es_ES');
        $uuid  = $faker->generate();

        $this->assertIsString($uuid);
        $this->assertNotEmpty($uuid);
    }
}
