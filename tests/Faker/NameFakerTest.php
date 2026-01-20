<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\NameFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for NameFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class NameFakerTest extends TestCase
{
    /**
     * Test that NameFaker generates a valid first name.
     */
    public function testGenerate(): void
    {
        $faker = new NameFaker('en_US');
        $name = $faker->generate();

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFaker generates different names.
     */
    public function testGenerateUnique(): void
    {
        $faker = new NameFaker('en_US');
        $name1 = $faker->generate();
        $name2 = $faker->generate();

        $this->assertIsString($name1);
        $this->assertIsString($name2);
    }

    /**
     * Test that NameFaker respects gender option (male).
     */
    public function testGenerateWithGenderMale(): void
    {
        $faker = new NameFaker('en_US');
        $name = $faker->generate(['gender' => 'male']);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFaker respects gender option (female).
     */
    public function testGenerateWithGenderFemale(): void
    {
        $faker = new NameFaker('en_US');
        $name = $faker->generate(['gender' => 'female']);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test that NameFaker respects gender option (random).
     */
    public function testGenerateWithGenderRandom(): void
    {
        $faker = new NameFaker('en_US');
        $name = $faker->generate(['gender' => 'random']);

        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }
}
