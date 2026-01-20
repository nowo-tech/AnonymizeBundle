<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\ConstantFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ConstantFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class ConstantFakerTest extends TestCase
{
    /**
     * Test that ConstantFaker returns the constant value.
     */
    public function testGenerate(): void
    {
        $faker = new ConstantFaker();
        $value = $faker->generate(['value' => 'constant_value']);

        $this->assertEquals('constant_value', $value);
    }

    /**
     * Test that ConstantFaker throws exception when value option is missing.
     */
    public function testGenerateThrowsExceptionWhenValueMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ConstantFaker requires a "value" option');

        $faker = new ConstantFaker();
        $faker->generate();
    }

    /**
     * Test that ConstantFaker works with different value types.
     */
    public function testGenerateWithDifferentTypes(): void
    {
        $faker = new ConstantFaker();

        $stringValue = $faker->generate(['value' => 'string']);
        $intValue = $faker->generate(['value' => 123]);
        $boolValue = $faker->generate(['value' => true]);
        $nullValue = $faker->generate(['value' => null]);

        $this->assertEquals('string', $stringValue);
        $this->assertEquals(123, $intValue);
        $this->assertTrue($boolValue);
        $this->assertNull($nullValue);
    }

    /**
     * Test that ConstantFaker always returns the same value.
     */
    public function testGenerateAlwaysSame(): void
    {
        $faker = new ConstantFaker();
        $constantValue = 'test_constant';

        $value1 = $faker->generate(['value' => $constantValue]);
        $value2 = $faker->generate(['value' => $constantValue]);
        $value3 = $faker->generate(['value' => $constantValue]);

        $this->assertEquals($constantValue, $value1);
        $this->assertEquals($constantValue, $value2);
        $this->assertEquals($constantValue, $value3);
        $this->assertEquals($value1, $value2);
        $this->assertEquals($value2, $value3);
    }
}
