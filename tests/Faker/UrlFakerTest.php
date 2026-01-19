<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\UrlFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for UrlFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class UrlFakerTest extends TestCase
{
    /**
     * Test that UrlFaker generates a valid URL.
     */
    public function testGenerate(): void
    {
        $faker = new UrlFaker('en_US');
        $url = $faker->generate();

        $this->assertIsString($url);
        $this->assertStringStartsWith('http', $url);
        $this->assertStringContainsString('://', $url);
    }

    /**
     * Test that UrlFaker respects scheme option.
     */
    public function testGenerateWithScheme(): void
    {
        $faker = new UrlFaker('en_US');
        $url = $faker->generate(['scheme' => 'http']);

        $this->assertIsString($url);
        $this->assertStringStartsWith('http://', $url);
    }

    /**
     * Test that UrlFaker respects domain option.
     */
    public function testGenerateWithDomain(): void
    {
        $faker = new UrlFaker('en_US');
        $url = $faker->generate(['domain' => 'example.com']);

        $this->assertIsString($url);
        $this->assertStringContainsString('example.com', $url);
    }

    /**
     * Test that UrlFaker respects path option (false).
     */
    public function testGenerateWithoutPath(): void
    {
        $faker = new UrlFaker('en_US');
        $url = $faker->generate(['path' => false]);

        $this->assertIsString($url);
        $parsed = parse_url($url);
        $this->assertIsArray($parsed);
        $this->assertArrayHasKey('host', $parsed);
        // Should not have path or have empty path
        if (isset($parsed['path'])) {
            $this->assertEmpty($parsed['path'] ?? '');
        }
    }
}
