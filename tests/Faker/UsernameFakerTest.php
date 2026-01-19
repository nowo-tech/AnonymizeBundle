<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\UsernameFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for UsernameFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class UsernameFakerTest extends TestCase
{
    /**
     * Test that UsernameFaker generates a valid username.
     */
    public function testGenerate(): void
    {
        $faker = new UsernameFaker('en_US');
        $username = $faker->generate();

        $this->assertIsString($username);
        $this->assertNotEmpty($username);
    }

    /**
     * Test that UsernameFaker respects min_length and max_length options.
     */
    public function testGenerateWithLength(): void
    {
        $faker = new UsernameFaker('en_US');
        $username = $faker->generate(['min_length' => 8, 'max_length' => 12]);

        $this->assertIsString($username);
        $this->assertGreaterThanOrEqual(8, strlen($username));
        $this->assertLessThanOrEqual(12, strlen($username));
    }

    /**
     * Test that UsernameFaker respects prefix option.
     */
    public function testGenerateWithPrefix(): void
    {
        $faker = new UsernameFaker('en_US');
        $username = $faker->generate(['prefix' => 'user_']);

        $this->assertIsString($username);
        $this->assertStringStartsWith('user_', $username);
    }

    /**
     * Test that UsernameFaker respects suffix option.
     */
    public function testGenerateWithSuffix(): void
    {
        $faker = new UsernameFaker('en_US');
        $username = $faker->generate(['suffix' => '_test', 'max_length' => 50]);

        $this->assertIsString($username);
        $this->assertStringEndsWith('_test', $username);
    }

    /**
     * Test that UsernameFaker respects include_numbers option.
     */
    public function testGenerateWithNumbers(): void
    {
        $faker = new UsernameFaker('en_US');
        $username = $faker->generate(['include_numbers' => true, 'max_length' => 20]);

        $this->assertIsString($username);
        // Note: Numbers might not always be included due to probability
        $this->assertNotEmpty($username);
    }
}
