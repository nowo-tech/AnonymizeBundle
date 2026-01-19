<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\EnumFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for EnumFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class EnumFakerTest extends TestCase
{
    /**
     * Test that EnumFaker generates a value from the provided list.
     */
    public function testGenerate(): void
    {
        $faker = new EnumFaker('en_US');
        $values = ['active', 'inactive', 'pending'];
        $value = $faker->generate(['values' => $values]);

        $this->assertContains($value, $values);
    }

    /**
     * Test that EnumFaker throws exception when values option is missing.
     */
    public function testGenerateThrowsExceptionWhenValuesMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EnumFaker requires a "values" option');

        $faker = new EnumFaker('en_US');
        $faker->generate();
    }

    /**
     * Test that EnumFaker throws exception when values is empty.
     */
    public function testGenerateThrowsExceptionWhenValuesEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EnumFaker requires a "values" option');

        $faker = new EnumFaker('en_US');
        $faker->generate(['values' => []]);
    }

    /**
     * Test that EnumFaker respects weighted option.
     */
    public function testGenerateWithWeighted(): void
    {
        $faker = new EnumFaker('en_US');
        $values = ['active', 'inactive', 'pending'];
        $weighted = ['active' => 50, 'inactive' => 30, 'pending' => 20];
        $value = $faker->generate(['values' => $values, 'weighted' => $weighted]);

        $this->assertContains($value, $values);
    }
}
