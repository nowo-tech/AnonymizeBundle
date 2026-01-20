<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\HashPreserveFaker;
use PHPUnit\Framework\TestCase;

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
        $hash = $faker->generate(['value' => 'test@example.com']);

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $hash);
    }

    /**
     * Test that HashPreserveFaker generates deterministic hashes.
     */
    public function testGenerateDeterministic(): void
    {
        $faker = new HashPreserveFaker();
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
        $faker = new HashPreserveFaker();
        $originalValue = 'test@example.com';

        $md5Hash = $faker->generate(['value' => $originalValue, 'algorithm' => 'md5']);
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
        $faker = new HashPreserveFaker();
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
        $faker = new HashPreserveFaker();
        $originalValue = 'test@example.com';

        $hash = $faker->generate(['value' => $originalValue, 'length' => 10]);

        $this->assertEquals(10, strlen($hash));
    }

    /**
     * Test that HashPreserveFaker respects preserve_format option for numeric values.
     */
    public function testGenerateWithPreserveFormatNumeric(): void
    {
        $faker = new HashPreserveFaker();
        $originalValue = '12345';

        $hash = $faker->generate(['value' => $originalValue, 'preserve_format' => true]);

        $this->assertIsString($hash);
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $hash);
        $this->assertLessThanOrEqual(20, strlen($hash));
    }
}
