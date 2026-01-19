<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\MaskingFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for MaskingFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class MaskingFakerTest extends TestCase
{
    /**
     * Test that MaskingFaker throws exception when value is missing.
     */
    public function testGenerateThrowsExceptionWithoutValue(): void
    {
        $faker = new MaskingFaker();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MaskingFaker requires a "value" option with the original value to mask.');

        $faker->generate();
    }

    /**
     * Test that MaskingFaker masks a value with default options.
     */
    public function testGenerateWithDefaultOptions(): void
    {
        $faker = new MaskingFaker();
        $masked = $faker->generate(['value' => '1234567890']);

        $this->assertIsString($masked);
        $this->assertEquals(10, strlen($masked));
        $this->assertStringStartsWith('1', $masked);
        $this->assertStringEndsWith('*', $masked);
    }

    /**
     * Test that MaskingFaker respects preserve_start option.
     */
    public function testGenerateWithPreserveStart(): void
    {
        $faker = new MaskingFaker();
        $masked = $faker->generate(['value' => '1234567890', 'preserve_start' => 3]);

        $this->assertIsString($masked);
        $this->assertStringStartsWith('123', $masked);
    }

    /**
     * Test that MaskingFaker respects preserve_end option.
     */
    public function testGenerateWithPreserveEnd(): void
    {
        $faker = new MaskingFaker();
        $masked = $faker->generate(['value' => '1234567890', 'preserve_end' => 2]);

        $this->assertIsString($masked);
        $this->assertStringEndsWith('90', $masked);
    }

    /**
     * Test that MaskingFaker respects mask_char option.
     */
    public function testGenerateWithMaskChar(): void
    {
        $faker = new MaskingFaker();
        $masked = $faker->generate(['value' => '1234567890', 'mask_char' => 'X']);

        $this->assertIsString($masked);
        $this->assertStringContainsString('X', $masked);
    }

    /**
     * Test that MaskingFaker respects mask_length option.
     */
    public function testGenerateWithMaskLength(): void
    {
        $faker = new MaskingFaker();
        $masked = $faker->generate(['value' => '1234567890', 'preserve_start' => 1, 'mask_length' => 5]);

        $this->assertIsString($masked);
        $this->assertEquals(6, strlen($masked)); // 1 preserved + 5 masked
    }

    /**
     * Test that MaskingFaker fully masks short values.
     */
    public function testGenerateWithShortValue(): void
    {
        $faker = new MaskingFaker();
        $masked = $faker->generate(['value' => '12', 'preserve_start' => 1, 'preserve_end' => 1]);

        $this->assertIsString($masked);
        $this->assertEquals(2, strlen($masked));
        $this->assertEquals('**', $masked);
    }
}
