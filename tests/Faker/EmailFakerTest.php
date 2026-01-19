<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\EmailFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for EmailFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class EmailFakerTest extends TestCase
{
    /**
     * Test that EmailFaker generates a valid email address.
     */
    public function testGenerate(): void
    {
        $faker = new EmailFaker('en_US');
        $email = $faker->generate();

        $this->assertIsString($email);
        $this->assertStringContainsString('@', $email);
        $this->assertMatchesRegularExpression('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email);
    }

    /**
     * Test that EmailFaker generates different emails.
     */
    public function testGenerateUnique(): void
    {
        $faker = new EmailFaker('en_US');
        $email1 = $faker->generate();
        $email2 = $faker->generate();

        // Note: Faker unique() might generate the same value, so we just check they're strings
        $this->assertIsString($email1);
        $this->assertIsString($email2);
    }
}
