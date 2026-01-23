<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\ColorFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ColorFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class ColorFakerTest extends TestCase
{
    /**
     * Test that ColorFaker generates a valid hex color.
     */
    public function testGenerate(): void
    {
        $faker = new ColorFaker('en_US');
        $color = $faker->generate();

        $this->assertIsString($color);
        $this->assertStringStartsWith('#', $color);
        $this->assertEquals(7, strlen($color)); // #RRGGBB
    }

    /**
     * Test that ColorFaker generates RGB format.
     */
    public function testGenerateRgb(): void
    {
        $faker = new ColorFaker('en_US');
        $color = $faker->generate(['format' => 'rgb']);

        $this->assertIsString($color);
        $this->assertStringStartsWith('rgb(', $color);
        $this->assertStringEndsWith(')', $color);
        $this->assertMatchesRegularExpression('/^rgb\(\d+, \d+, \d+\)$/', $color);
    }

    /**
     * Test that ColorFaker generates RGBA format.
     */
    public function testGenerateRgba(): void
    {
        $faker = new ColorFaker('en_US');
        $color = $faker->generate(['format' => 'rgba']);

        $this->assertIsString($color);
        $this->assertStringStartsWith('rgba(', $color);
        $this->assertStringEndsWith(')', $color);
        $this->assertMatchesRegularExpression('/^rgba\(\d+, \d+, \d+, \d+\.\d{2}\)$/', $color);
    }

    /**
     * Test that ColorFaker respects alpha option.
     */
    public function testGenerateWithAlpha(): void
    {
        $faker = new ColorFaker('en_US');
        $color = $faker->generate(['format' => 'rgba', 'alpha' => 0.5]);

        $this->assertIsString($color);
        $this->assertStringContainsString('0.50', $color);
    }

    /**
     * Test that ColorFaker handles different alpha values.
     */
    public function testGenerateWithDifferentAlphaValues(): void
    {
        $faker = new ColorFaker('en_US');
        $alphaValues = [0.0, 0.25, 0.5, 0.75, 1.0];
        
        foreach ($alphaValues as $alpha) {
            $color = $faker->generate(['format' => 'rgba', 'alpha' => $alpha]);
            $this->assertIsString($color);
            $this->assertStringStartsWith('rgba(', $color);
        }
    }

    /**
     * Test that ColorFaker handles invalid format gracefully.
     */
    public function testGenerateWithInvalidFormat(): void
    {
        $faker = new ColorFaker('en_US');
        // Invalid format should default to hex
        $color = $faker->generate(['format' => 'invalid']);

        $this->assertIsString($color);
        $this->assertStringStartsWith('#', $color);
    }

    /**
     * Test that ColorFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new ColorFaker('en_US');
        $this->assertInstanceOf(ColorFaker::class, $faker);
    }

    /**
     * Test that ColorFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new ColorFaker('es_ES');
        $color = $faker->generate();

        $this->assertIsString($color);
        $this->assertStringStartsWith('#', $color);
    }

    /**
     * Test that ColorFaker generates different colors.
     */
    public function testGenerateDifferentColors(): void
    {
        $faker = new ColorFaker('en_US');
        $colors = [];
        
        for ($i = 0; $i < 10; $i++) {
            $colors[] = $faker->generate();
        }
        
        // Should have some variation
        $uniqueColors = array_unique($colors);
        $this->assertGreaterThan(1, count($uniqueColors));
    }
}
