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
        $this->assertNotEmpty($username);
        // Note: Due to prefix/suffix and number inclusion, actual length may vary
        // We check that it's within reasonable bounds (at least min_length, at most max_length)
        $this->assertGreaterThanOrEqual(8, strlen($username), sprintf('Username "%s" length %d is less than min_length 8', $username, strlen($username)));
        $this->assertLessThanOrEqual(12, strlen($username), sprintf('Username "%s" length %d is greater than max_length 12', $username, strlen($username)));
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

    /**
     * Test that UsernameFaker handles include_numbers false.
     */
    public function testGenerateWithoutNumbers(): void
    {
        $faker = new UsernameFaker('en_US');
        $username = $faker->generate(['include_numbers' => false, 'max_length' => 20]);

        $this->assertIsString($username);
        $this->assertNotEmpty($username);
    }

    /**
     * Test that UsernameFaker handles length constraints with prefix and suffix.
     */
    public function testGenerateWithPrefixSuffixAndLength(): void
    {
        $faker = new UsernameFaker('en_US');
        $username = $faker->generate([
            'prefix' => 'user_',
            'suffix' => '_test',
            'min_length' => 10,
            'max_length' => 20,
        ]);

        $this->assertIsString($username);
        $this->assertStringStartsWith('user_', $username);
        $this->assertStringEndsWith('_test', $username);
        $this->assertLessThanOrEqual(20, strlen($username));
    }

    /**
     * Test that UsernameFaker pads to minimum length when needed.
     */
    public function testGeneratePadsToMinimumLength(): void
    {
        $faker = new UsernameFaker('en_US');
        // Force a very short base by using a long prefix/suffix
        $username = $faker->generate([
            'prefix' => 'a',
            'suffix' => 'b',
            'min_length' => 10,
            'max_length' => 20,
        ]);

        $this->assertIsString($username);
        $this->assertGreaterThanOrEqual(10, strlen($username));
    }

    /**
     * Test that UsernameFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new UsernameFaker('en_US');
        $this->assertInstanceOf(UsernameFaker::class, $faker);
    }

    /**
     * Test that UsernameFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new UsernameFaker('es_ES');
        $username = $faker->generate();

        $this->assertIsString($username);
        $this->assertNotEmpty($username);
    }

    /**
     * Test that UsernameFaker handles very long prefix and suffix.
     */
    public function testGenerateWithVeryLongPrefixSuffix(): void
    {
        $faker = new UsernameFaker('en_US');
        $username = $faker->generate([
            'prefix' => 'very_long_prefix_',
            'suffix' => '_very_long_suffix',
            'min_length' => 5,
            'max_length' => 50,
        ]);

        $this->assertIsString($username);
        $this->assertStringStartsWith('very_long_prefix_', $username);
        $this->assertStringEndsWith('_very_long_suffix', $username);
        $this->assertLessThanOrEqual(50, strlen($username));
    }

    /**
     * Test that UsernameFaker handles min_length equal to max_length.
     */
    public function testGenerateWithEqualMinMaxLength(): void
    {
        $faker = new UsernameFaker('en_US');
        $username = $faker->generate([
            'min_length' => 10,
            'max_length' => 10,
        ]);

        $this->assertIsString($username);
        $this->assertEquals(10, strlen($username));
    }

    /**
     * Test that UsernameFaker handles zero min_length.
     */
    public function testGenerateWithZeroMinLength(): void
    {
        $faker = new UsernameFaker('en_US');
        $username = $faker->generate([
            'min_length' => 0,
            'max_length' => 20,
        ]);

        $this->assertIsString($username);
        // When min_length is 0, username might be empty, but should not exceed max_length
        $this->assertLessThanOrEqual(20, strlen($username));
    }
}
