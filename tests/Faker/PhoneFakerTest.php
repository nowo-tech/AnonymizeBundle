<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\PhoneFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for PhoneFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class PhoneFakerTest extends TestCase
{
    /**
     * Test that PhoneFaker generates a valid phone number.
     */
    public function testGenerate(): void
    {
        $faker = new PhoneFaker('en_US');
        $phone = $faker->generate();

        $this->assertIsString($phone);
        $this->assertNotEmpty($phone);
    }

    /**
     * Test that PhoneFaker generates different phone numbers.
     */
    public function testGenerateUnique(): void
    {
        $faker = new PhoneFaker('en_US');
        $phone1 = $faker->generate();
        $phone2 = $faker->generate();

        $this->assertIsString($phone1);
        $this->assertIsString($phone2);
    }
}
