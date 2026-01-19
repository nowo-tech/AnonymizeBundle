<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\HashFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for HashFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class HashFakerTest extends TestCase
{
    /**
     * Test that HashFaker generates a valid hash.
     */
    public function testGenerate(): void
    {
        $faker = new HashFaker('en_US');
        $hash = $faker->generate();

        $this->assertIsString($hash);
        $this->assertEquals(64, strlen($hash)); // SHA256 default length
    }

    /**
     * Test that HashFaker generates MD5 hash.
     */
    public function testGenerateMd5(): void
    {
        $faker = new HashFaker('en_US');
        $hash = $faker->generate(['algorithm' => 'md5']);

        $this->assertIsString($hash);
        $this->assertEquals(32, strlen($hash)); // MD5 length
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $hash);
    }

    /**
     * Test that HashFaker generates SHA1 hash.
     */
    public function testGenerateSha1(): void
    {
        $faker = new HashFaker('en_US');
        $hash = $faker->generate(['algorithm' => 'sha1']);

        $this->assertIsString($hash);
        $this->assertEquals(40, strlen($hash)); // SHA1 length
        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $hash);
    }

    /**
     * Test that HashFaker generates SHA256 hash.
     */
    public function testGenerateSha256(): void
    {
        $faker = new HashFaker('en_US');
        $hash = $faker->generate(['algorithm' => 'sha256']);

        $this->assertIsString($hash);
        $this->assertEquals(64, strlen($hash)); // SHA256 length
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
    }

    /**
     * Test that HashFaker respects length option.
     */
    public function testGenerateWithLength(): void
    {
        $faker = new HashFaker('en_US');
        $hash = $faker->generate(['length' => 16]);

        $this->assertIsString($hash);
        $this->assertEquals(16, strlen($hash));
    }
}
