<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\SurnameFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for SurnameFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class SurnameFakerTest extends TestCase
{
    /**
     * Test that SurnameFaker generates a valid surname.
     */
    public function testGenerate(): void
    {
        $faker = new SurnameFaker('en_US');
        $surname = $faker->generate();

        $this->assertIsString($surname);
        $this->assertNotEmpty($surname);
    }

    /**
     * Test that SurnameFaker generates different surnames.
     */
    public function testGenerateUnique(): void
    {
        $faker = new SurnameFaker('en_US');
        $surname1 = $faker->generate();
        $surname2 = $faker->generate();

        $this->assertIsString($surname1);
        $this->assertIsString($surname2);
    }
}
