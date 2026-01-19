<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\UuidFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for UuidFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class UuidFakerTest extends TestCase
{
    /**
     * Test that UuidFaker generates a valid UUID.
     */
    public function testGenerate(): void
    {
        $faker = new UuidFaker('en_US');
        $uuid = $faker->generate();

        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Test that UuidFaker generates UUID v4.
     */
    public function testGenerateV4(): void
    {
        $faker = new UuidFaker('en_US');
        $uuid = $faker->generate(['version' => 4]);

        $this->assertIsString($uuid);
        // UUID v4 format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx where y is 8, 9, a, or b
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Test that UuidFaker generates UUID v1.
     */
    public function testGenerateV1(): void
    {
        $faker = new UuidFaker('en_US');
        $uuid = $faker->generate(['version' => 1]);

        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Test that UuidFaker respects format option (with_dashes).
     */
    public function testGenerateWithDashes(): void
    {
        $faker = new UuidFaker('en_US');
        $uuid = $faker->generate(['format' => 'with_dashes']);

        $this->assertIsString($uuid);
        $this->assertStringContainsString('-', $uuid);
        $this->assertEquals(36, strlen($uuid));
    }

    /**
     * Test that UuidFaker respects format option (without_dashes).
     */
    public function testGenerateWithoutDashes(): void
    {
        $faker = new UuidFaker('en_US');
        $uuid = $faker->generate(['format' => 'without_dashes']);

        $this->assertIsString($uuid);
        $this->assertStringNotContainsString('-', $uuid);
        $this->assertEquals(32, strlen($uuid));
    }
}
