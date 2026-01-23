<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\FileFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for FileFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class FileFakerTest extends TestCase
{
    /**
     * Test that FileFaker generates a valid filename.
     */
    public function testGenerate(): void
    {
        $faker = new FileFaker('en_US');
        $file = $faker->generate();

        $this->assertIsString($file);
        $this->assertNotEmpty($file);
        $this->assertStringContainsString('.', $file);
    }

    /**
     * Test that FileFaker respects extension option.
     */
    public function testGenerateWithExtension(): void
    {
        $faker = new FileFaker('en_US');
        $file = $faker->generate(['extension' => 'pdf']);

        $this->assertIsString($file);
        $this->assertStringEndsWith('.pdf', $file);
    }

    /**
     * Test that FileFaker respects directory option.
     */
    public function testGenerateWithDirectory(): void
    {
        $faker = new FileFaker('en_US');
        $file = $faker->generate(['directory' => 'uploads']);

        $this->assertIsString($file);
        $this->assertStringStartsWith('uploads/', $file);
    }

    /**
     * Test that FileFaker respects absolute option.
     */
    public function testGenerateAbsolute(): void
    {
        $faker = new FileFaker('en_US');
        $file = $faker->generate(['absolute' => true]);

        $this->assertIsString($file);
        $this->assertStringStartsWith('/', $file);
    }

    /**
     * Test that FileFaker combines directory and absolute options.
     */
    public function testGenerateWithDirectoryAndAbsolute(): void
    {
        $faker = new FileFaker('en_US');
        $file = $faker->generate(['directory' => 'uploads', 'absolute' => true]);

        $this->assertIsString($file);
        $this->assertStringStartsWith('/', $file);
        $this->assertStringContainsString('uploads/', $file);
    }

    /**
     * Test that FileFaker handles directory with trailing slash.
     */
    public function testGenerateWithDirectoryTrailingSlash(): void
    {
        $faker = new FileFaker('en_US');
        $file = $faker->generate(['directory' => 'uploads/']);

        $this->assertIsString($file);
        $this->assertStringStartsWith('uploads/', $file);
        $this->assertStringNotContainsString('//', $file);
    }

    /**
     * Test that FileFaker handles extension with leading dot.
     */
    public function testGenerateWithExtensionLeadingDot(): void
    {
        $faker = new FileFaker('en_US');
        $file = $faker->generate(['extension' => '.pdf']);

        $this->assertIsString($file);
        $this->assertStringEndsWith('.pdf', $file);
        $this->assertStringNotContainsString('..', $file);
    }

    /**
     * Test that FileFaker handles absolute path without directory.
     */
    public function testGenerateAbsoluteWithoutDirectory(): void
    {
        $faker = new FileFaker('en_US');
        $file = $faker->generate(['absolute' => true]);

        $this->assertIsString($file);
        $this->assertStringStartsWith('/', $file);
        $this->assertStringContainsString('.', $file);
    }

    /**
     * Test that FileFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new FileFaker('en_US');
        $this->assertInstanceOf(FileFaker::class, $faker);
    }

    /**
     * Test that FileFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new FileFaker('es_ES');
        $file = $faker->generate();

        $this->assertIsString($file);
        $this->assertNotEmpty($file);
    }
}
