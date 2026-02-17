<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use InvalidArgumentException;
use Nowo\AnonymizeBundle\Faker\HashPreserveFaker;
use PHPUnit\Framework\TestCase;

use function strlen;

/**
 * Test case for HashPreserveFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class HashPreserveFakerTest extends TestCase
{
    /**
     * Test that HashPreserveFaker generates a hash.
     */
    public function testGenerate(): void
    {
        $faker = new HashPreserveFaker();
        $hash  = $faker->generate(['value' => 'test@example.com']);

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $hash);
    }

    /**
     * Test that HashPreserveFaker generates deterministic hashes.
     */
    public function testGenerateDeterministic(): void
    {
        $faker         = new HashPreserveFaker();
        $originalValue = 'test@example.com';

        $hash1 = $faker->generate(['value' => $originalValue]);
        $hash2 = $faker->generate(['value' => $originalValue]);

        $this->assertEquals($hash1, $hash2);
        $this->assertIsString($hash1);
        $this->assertNotEmpty($hash1);
    }

    /**
     * Test that HashPreserveFaker respects algorithm option.
     */
    public function testGenerateWithAlgorithm(): void
    {
        $faker         = new HashPreserveFaker();
        $originalValue = 'test@example.com';

        $md5Hash    = $faker->generate(['value' => $originalValue, 'algorithm' => 'md5']);
        $sha256Hash = $faker->generate(['value' => $originalValue, 'algorithm' => 'sha256']);

        $this->assertNotEquals($md5Hash, $sha256Hash);
        $this->assertEquals(32, strlen($md5Hash)); // MD5 is 32 chars
        $this->assertEquals(64, strlen($sha256Hash)); // SHA256 is 64 chars
    }

    /**
     * Test that HashPreserveFaker respects salt option.
     */
    public function testGenerateWithSalt(): void
    {
        $faker         = new HashPreserveFaker();
        $originalValue = 'test@example.com';

        $hash1 = $faker->generate(['value' => $originalValue, 'salt' => 'salt1']);
        $hash2 = $faker->generate(['value' => $originalValue, 'salt' => 'salt2']);

        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * Test that HashPreserveFaker respects length option.
     */
    public function testGenerateWithLength(): void
    {
        $faker         = new HashPreserveFaker();
        $originalValue = 'test@example.com';

        $hash = $faker->generate(['value' => $originalValue, 'length' => 10]);

        $this->assertEquals(10, strlen($hash));
    }

    /**
     * Test that HashPreserveFaker respects preserve_format option for numeric values.
     */
    public function testGenerateWithPreserveFormatNumeric(): void
    {
        $faker         = new HashPreserveFaker();
        $originalValue = '12345';

        $hash = $faker->generate(['value' => $originalValue, 'preserve_format' => true]);

        $this->assertIsString($hash);
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $hash);
        $this->assertLessThanOrEqual(20, strlen($hash));
    }

    /**
     * Test that HashPreserveFaker uses original_value option.
     */
    public function testGenerateWithOriginalValue(): void
    {
        $faker = new HashPreserveFaker();
        $hash1 = $faker->generate(['original_value' => 'test@example.com']);
        $hash2 = $faker->generate(['value' => 'test@example.com']);

        $this->assertEquals($hash1, $hash2);
    }

    /**
     * Test that HashPreserveFaker throws exception when no value provided.
     */
    public function testGenerateThrowsExceptionWhenNoValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('HashPreserveFaker requires an "original_value"');

        $faker = new HashPreserveFaker();
        $faker->generate();
    }

    /**
     * Test that HashPreserveFaker respects sha1 algorithm.
     */
    public function testGenerateWithSha1(): void
    {
        $faker = new HashPreserveFaker();
        $hash  = $faker->generate(['value' => 'test', 'algorithm' => 'sha1']);

        $this->assertIsString($hash);
        $this->assertEquals(40, strlen($hash)); // SHA1 is 40 chars
    }

    /**
     * Test that HashPreserveFaker respects sha512 algorithm.
     */
    public function testGenerateWithSha512(): void
    {
        $faker = new HashPreserveFaker();
        $hash  = $faker->generate(['value' => 'test', 'algorithm' => 'sha512']);

        $this->assertIsString($hash);
        $this->assertEquals(128, strlen($hash)); // SHA512 is 128 chars
    }

    /**
     * Test that HashPreserveFaker handles invalid algorithm gracefully.
     */
    public function testGenerateWithInvalidAlgorithm(): void
    {
        $faker = new HashPreserveFaker();
        $hash  = $faker->generate(['value' => 'test', 'algorithm' => 'invalid']);

        $this->assertIsString($hash);
        // Should default to sha256
        $this->assertEquals(64, strlen($hash));
    }

    /**
     * Test that HashPreserveFaker handles preserve_format with non-numeric values.
     */
    public function testGenerateWithPreserveFormatNonNumeric(): void
    {
        $faker = new HashPreserveFaker();
        $hash  = $faker->generate(['value' => 'test@example.com', 'preserve_format' => true]);

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
        // Should not be all numeric since original is not numeric
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $hash);
    }

    /**
     * Test that HashPreserveFaker handles zero length.
     */
    public function testGenerateWithZeroLength(): void
    {
        $faker = new HashPreserveFaker();
        $hash  = $faker->generate(['value' => 'test', 'length' => 0]);

        $this->assertIsString($hash);
        // Should return full hash when length is 0
        $this->assertEquals(64, strlen($hash));
    }

    /**
     * Test that HashPreserveFaker handles negative length.
     */
    public function testGenerateWithNegativeLength(): void
    {
        $faker = new HashPreserveFaker();
        $hash  = $faker->generate(['value' => 'test', 'length' => -5]);

        $this->assertIsString($hash);
        // Should return full hash when length is negative
        $this->assertEquals(64, strlen($hash));
    }

    /**
     * Test that HashPreserveFaker handles preserve_format with empty hash result.
     */
    public function testGenerateWithPreserveFormatEmptyHash(): void
    {
        $faker = new HashPreserveFaker();
        // This test ensures that if preserve_format results in empty hash, it defaults to '0'
        $hash = $faker->generate(['value' => '0', 'preserve_format' => true, 'length' => 1]);

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
    }

    /**
     * Test that HashPreserveFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new HashPreserveFaker();
        $this->assertInstanceOf(HashPreserveFaker::class, $faker);
    }

    /**
     * Test that HashPreserveFaker handles preserve_format with hash longer than 20 characters.
     */
    public function testGenerateWithPreserveFormatLongHash(): void
    {
        $faker = new HashPreserveFaker();
        // Use a value that will generate a hash with many digits after preserve_format
        // We'll use a numeric value and ensure the hash has many numeric characters
        $hash = $faker->generate(['value' => '12345678901234567890', 'preserve_format' => true]);

        $this->assertIsString($hash);
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $hash);
        // Should be limited to 20 characters
        $this->assertLessThanOrEqual(20, strlen($hash));
    }

    /**
     * Test that HashPreserveFaker handles preserve_format with float numeric value.
     */
    public function testGenerateWithPreserveFormatFloatNumeric(): void
    {
        $faker = new HashPreserveFaker();
        $hash  = $faker->generate(['value' => '123.45', 'preserve_format' => true]);

        $this->assertIsString($hash);
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $hash);
        $this->assertLessThanOrEqual(20, strlen($hash));
    }

    /**
     * Test that HashPreserveFaker handles preserve_format with integer value.
     */
    public function testGenerateWithPreserveFormatInteger(): void
    {
        $faker = new HashPreserveFaker();
        $hash  = $faker->generate(['value' => 12345, 'preserve_format' => true]);

        $this->assertIsString($hash);
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $hash);
        $this->assertLessThanOrEqual(20, strlen($hash));
    }

    /**
     * Test that HashPreserveFaker handles preserve_format with float value.
     */
    public function testGenerateWithPreserveFormatFloat(): void
    {
        $faker = new HashPreserveFaker();
        $hash  = $faker->generate(['value' => 123.45, 'preserve_format' => true]);

        $this->assertIsString($hash);
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $hash);
        $this->assertLessThanOrEqual(20, strlen($hash));
    }
}
