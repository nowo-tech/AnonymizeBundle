<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\HashFaker;
use PHPUnit\Framework\TestCase;

use function strlen;

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
        $hash  = $faker->generate();

        $this->assertIsString($hash);
        $this->assertEquals(64, strlen($hash)); // SHA256 default length
    }

    /**
     * Test that HashFaker generates MD5 hash.
     */
    public function testGenerateMd5(): void
    {
        $faker = new HashFaker('en_US');
        $hash  = $faker->generate(['algorithm' => 'md5']);

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
        $hash  = $faker->generate(['algorithm' => 'sha1']);

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
        $hash  = $faker->generate(['algorithm' => 'sha256']);

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
        $hash  = $faker->generate(['length' => 16]);

        $this->assertIsString($hash);
        $this->assertEquals(16, strlen($hash));
    }

    /**
     * Test that HashFaker generates SHA512 hash.
     */
    public function testGenerateSha512(): void
    {
        $faker = new HashFaker('en_US');
        $hash  = $faker->generate(['algorithm' => 'sha512']);

        $this->assertIsString($hash);
        $this->assertEquals(128, strlen($hash)); // SHA512 length
        $this->assertMatchesRegularExpression('/^[0-9a-f]{128}$/', $hash);
    }

    /**
     * Test that HashFaker generates different hashes.
     */
    public function testGenerateUnique(): void
    {
        $faker = new HashFaker('en_US');
        $hash1 = $faker->generate();
        $hash2 = $faker->generate();

        $this->assertIsString($hash1);
        $this->assertIsString($hash2);
        // Hashes should be different (very unlikely to be the same)
        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * Test that HashFaker handles invalid algorithm gracefully.
     */
    public function testGenerateWithInvalidAlgorithm(): void
    {
        $faker = new HashFaker('en_US');
        $hash  = $faker->generate(['algorithm' => 'invalid_algorithm']);

        $this->assertIsString($hash);
        // Should default to sha256
        $this->assertEquals(64, strlen($hash));
    }

    /**
     * Test that HashFaker respects length option with different algorithms.
     */
    public function testGenerateWithLengthAndAlgorithm(): void
    {
        $faker = new HashFaker('en_US');
        $hash  = $faker->generate(['algorithm' => 'md5', 'length' => 10]);

        $this->assertIsString($hash);
        $this->assertEquals(10, strlen($hash));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{10}$/', $hash);
    }

    /**
     * Test that HashFaker handles zero length.
     */
    public function testGenerateWithZeroLength(): void
    {
        $faker = new HashFaker('en_US');
        $hash  = $faker->generate(['length' => 0]);

        $this->assertIsString($hash);
        // Should return full hash when length is 0 or invalid
        $this->assertEquals(64, strlen($hash));
    }

    /**
     * Test that HashFaker handles negative length.
     */
    public function testGenerateWithNegativeLength(): void
    {
        $faker = new HashFaker('en_US');
        $hash  = $faker->generate(['length' => -5]);

        $this->assertIsString($hash);
        // Should return full hash when length is negative
        $this->assertEquals(64, strlen($hash));
    }
}
