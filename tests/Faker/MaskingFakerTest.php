<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use InvalidArgumentException;
use Nowo\AnonymizeBundle\Faker\MaskingFaker;
use PHPUnit\Framework\TestCase;

use function strlen;

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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MaskingFaker requires an "original_value" (or "value") option with the original value to mask.');

        $faker->generate();
    }

    /**
     * Test that MaskingFaker masks a value with default options.
     */
    public function testGenerateWithDefaultOptions(): void
    {
        $faker  = new MaskingFaker();
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
        $faker  = new MaskingFaker();
        $masked = $faker->generate(['value' => '1234567890', 'preserve_start' => 3]);

        $this->assertIsString($masked);
        $this->assertStringStartsWith('123', $masked);
    }

    /**
     * Test that MaskingFaker respects preserve_end option.
     */
    public function testGenerateWithPreserveEnd(): void
    {
        $faker  = new MaskingFaker();
        $masked = $faker->generate(['value' => '1234567890', 'preserve_end' => 2]);

        $this->assertIsString($masked);
        $this->assertStringEndsWith('90', $masked);
    }

    /**
     * Test that MaskingFaker respects mask_char option.
     */
    public function testGenerateWithMaskChar(): void
    {
        $faker  = new MaskingFaker();
        $masked = $faker->generate(['value' => '1234567890', 'mask_char' => 'X']);

        $this->assertIsString($masked);
        $this->assertStringContainsString('X', $masked);
    }

    /**
     * Test that MaskingFaker respects mask_length option.
     */
    public function testGenerateWithMaskLength(): void
    {
        $faker  = new MaskingFaker();
        $masked = $faker->generate(['value' => '1234567890', 'preserve_start' => 1, 'mask_length' => 5]);

        $this->assertIsString($masked);
        $this->assertEquals(6, strlen($masked)); // 1 preserved + 5 masked
    }

    /**
     * Test that MaskingFaker fully masks short values.
     */
    public function testGenerateWithShortValue(): void
    {
        $faker  = new MaskingFaker();
        $masked = $faker->generate(['value' => '12', 'preserve_start' => 1, 'preserve_end' => 1]);

        $this->assertIsString($masked);
        $this->assertEquals(2, strlen($masked));
        $this->assertEquals('**', $masked);
    }

    /**
     * Test that MaskingFaker uses original_value option.
     */
    public function testGenerateWithOriginalValue(): void
    {
        $faker   = new MaskingFaker();
        $masked1 = $faker->generate(['original_value' => '1234567890']);
        $masked2 = $faker->generate(['value' => '1234567890']);

        $this->assertEquals($masked1, $masked2);
    }

    /**
     * Test that MaskingFaker throws exception when value is not a string.
     */
    public function testGenerateThrowsExceptionWhenValueNotString(): void
    {
        $faker = new MaskingFaker();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MaskingFaker requires an "original_value" (or "value") option with the original value to mask.');

        $faker->generate(['value' => 12345]);
    }

    /**
     * Test that MaskingFaker handles preserve_start and preserve_end together.
     */
    public function testGenerateWithPreserveStartAndEnd(): void
    {
        $faker  = new MaskingFaker();
        $masked = $faker->generate(['value' => '1234567890', 'preserve_start' => 2, 'preserve_end' => 2]);

        $this->assertIsString($masked);
        $this->assertStringStartsWith('12', $masked);
        $this->assertStringEndsWith('90', $masked);
        $this->assertEquals(10, strlen($masked));
    }

    /**
     * Test that MaskingFaker handles zero preserve_start.
     */
    public function testGenerateWithZeroPreserveStart(): void
    {
        $faker  = new MaskingFaker();
        $masked = $faker->generate(['value' => '1234567890', 'preserve_start' => 0, 'preserve_end' => 2]);

        $this->assertIsString($masked);
        $this->assertStringEndsWith('90', $masked);
        $this->assertStringStartsWith('*', $masked);
    }

    /**
     * Test that MaskingFaker handles mask_length shorter than calculated.
     */
    public function testGenerateWithShorterMaskLength(): void
    {
        $faker  = new MaskingFaker();
        $masked = $faker->generate(['value' => '1234567890', 'preserve_start' => 1, 'preserve_end' => 1, 'mask_length' => 3]);

        $this->assertIsString($masked);
        $this->assertEquals(5, strlen($masked)); // 1 + 3 + 1
    }

    /**
     * Test that MaskingFaker handles mask_length longer than calculated.
     */
    public function testGenerateWithLongerMaskLength(): void
    {
        $faker  = new MaskingFaker();
        $masked = $faker->generate(['value' => '1234567890', 'preserve_start' => 1, 'preserve_end' => 1, 'mask_length' => 20]);

        $this->assertIsString($masked);
        $this->assertEquals(22, strlen($masked)); // 1 + 20 + 1
    }

    /**
     * Test that MaskingFaker handles single character value.
     */
    public function testGenerateWithSingleCharacter(): void
    {
        $faker  = new MaskingFaker();
        $masked = $faker->generate(['value' => 'A']);

        $this->assertIsString($masked);
        $this->assertEquals('*', $masked);
    }

    /**
     * Test that MaskingFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new MaskingFaker();
        $this->assertInstanceOf(MaskingFaker::class, $faker);
    }
}
