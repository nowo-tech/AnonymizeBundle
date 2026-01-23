<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\NullFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for NullFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class NullFakerTest extends TestCase
{
    /**
     * Test that NullFaker always returns null.
     */
    public function testGenerateAlwaysReturnsNull(): void
    {
        $faker = new NullFaker();

        $result = $faker->generate([]);
        $this->assertNull($result);

        $result2 = $faker->generate(['any' => 'option']);
        $this->assertNull($result2);

        $result3 = $faker->generate(['value' => 'ignored']);
        $this->assertNull($result3);
    }

    /**
     * Test that NullFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new NullFaker();
        $this->assertInstanceOf(NullFaker::class, $faker);
    }

    /**
     * Test that NullFaker works with empty options.
     */
    public function testGenerateWithEmptyOptions(): void
    {
        $faker = new NullFaker();
        $result = $faker->generate([]);
        $this->assertNull($result);
    }

    /**
     * Test that NullFaker ignores all options.
     */
    public function testGenerateIgnoresOptions(): void
    {
        $faker = new NullFaker();

        // Should return null regardless of options
        $result1 = $faker->generate(['value' => 'test']);
        $this->assertNull($result1);

        $result2 = $faker->generate(['bypass_entity_exclusion' => true]);
        $this->assertNull($result2);

        $result3 = $faker->generate(['any' => 'option', 'other' => 'value']);
        $this->assertNull($result3);
    }
}
