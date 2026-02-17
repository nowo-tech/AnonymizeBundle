<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\PasswordFaker;
use PHPUnit\Framework\TestCase;

use function strlen;

/**
 * Test case for PasswordFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class PasswordFakerTest extends TestCase
{
    /**
     * Test that PasswordFaker generates a valid password.
     */
    public function testGenerate(): void
    {
        $faker    = new PasswordFaker('en_US');
        $password = $faker->generate();

        $this->assertIsString($password);
        $this->assertGreaterThanOrEqual(12, strlen($password));
    }

    /**
     * Test that PasswordFaker respects length option.
     */
    public function testGenerateWithLength(): void
    {
        $faker    = new PasswordFaker('en_US');
        $password = $faker->generate(['length' => 16]);

        $this->assertIsString($password);
        $this->assertEquals(16, strlen($password));
    }

    /**
     * Test that PasswordFaker respects include_special option.
     */
    public function testGenerateWithSpecialChars(): void
    {
        $faker    = new PasswordFaker('en_US');
        $password = $faker->generate(['include_special' => true, 'length' => 20]);

        $this->assertIsString($password);
        $this->assertMatchesRegularExpression('/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/', $password);
    }

    /**
     * Test that PasswordFaker respects include_numbers option.
     */
    public function testGenerateWithNumbers(): void
    {
        $faker    = new PasswordFaker('en_US');
        $password = $faker->generate(['include_numbers' => true, 'length' => 20]);

        $this->assertIsString($password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);
    }

    /**
     * Test that PasswordFaker respects include_uppercase option.
     */
    public function testGenerateWithUppercase(): void
    {
        $faker    = new PasswordFaker('en_US');
        $password = $faker->generate(['include_uppercase' => true, 'length' => 20]);

        $this->assertIsString($password);
        $this->assertMatchesRegularExpression('/[A-Z]/', $password);
    }
}
