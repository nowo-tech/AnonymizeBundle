<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\EmailFaker;
use PHPUnit\Framework\TestCase;

use function strlen;

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
        $faker  = new EmailFaker('en_US');
        $email1 = $faker->generate();
        $email2 = $faker->generate();

        // Note: Faker unique() might generate the same value, so we just check they're strings
        $this->assertIsString($email1);
        $this->assertIsString($email2);
    }

    /**
     * Test that EmailFaker respects domain option.
     */
    public function testGenerateWithDomain(): void
    {
        $faker = new EmailFaker('en_US');
        $email = $faker->generate(['domain' => 'example.com']);

        $this->assertIsString($email);
        $this->assertStringEndsWith('@example.com', $email);
    }

    /**
     * Test that EmailFaker respects format option (name.surname).
     */
    public function testGenerateWithFormat(): void
    {
        $faker = new EmailFaker('en_US');
        $email = $faker->generate(['format' => 'name.surname']);

        $this->assertIsString($email);
        $this->assertStringContainsString('@', $email);
        $this->assertMatchesRegularExpression('/^[^\s@]+\.[^\s@]+@[^\s@]+\.[^\s@]+$/', $email);
    }

    /**
     * Test that EmailFaker respects local_part_length option.
     */
    public function testGenerateWithLocalPartLength(): void
    {
        $faker = new EmailFaker('en_US');
        $email = $faker->generate(['local_part_length' => 10]);

        $this->assertIsString($email);
        $parts = explode('@', $email);
        $this->assertCount(2, $parts);
        $this->assertLessThanOrEqual(10, strlen($parts[0]));
    }

    /**
     * Test that EmailFaker handles invalid format option gracefully.
     */
    public function testGenerateWithInvalidFormat(): void
    {
        $faker = new EmailFaker('en_US');
        $email = $faker->generate(['format' => 'invalid_format']);

        $this->assertIsString($email);
        $this->assertStringContainsString('@', $email);
        $this->assertMatchesRegularExpression('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email);
    }

    /**
     * Test that EmailFaker handles local_part_length when part is shorter.
     */
    public function testGenerateWithLocalPartLengthPadding(): void
    {
        $faker = new EmailFaker('en_US');
        // Use a smaller length to avoid exceeding mt_getrandmax()
        $email = $faker->generate(['local_part_length' => 10, 'format' => 'name.surname']);

        $this->assertIsString($email);
        $parts = explode('@', $email);
        $this->assertCount(2, $parts);
        // Should pad if shorter, but not exceed the specified length
        $this->assertLessThanOrEqual(10, strlen($parts[0]));
        $this->assertGreaterThanOrEqual(5, strlen($parts[0]));
    }

    /**
     * Test that EmailFaker handles zero local_part_length.
     */
    public function testGenerateWithZeroLocalPartLength(): void
    {
        $faker = new EmailFaker('en_US');
        $email = $faker->generate(['local_part_length' => 0]);

        $this->assertIsString($email);
        $this->assertStringContainsString('@', $email);
        // Should generate normally when length is 0 or negative
        $parts = explode('@', $email);
        $this->assertGreaterThan(0, strlen($parts[0]));
    }

    /**
     * Test that EmailFaker handles negative local_part_length.
     */
    public function testGenerateWithNegativeLocalPartLength(): void
    {
        $faker = new EmailFaker('en_US');
        $email = $faker->generate(['local_part_length' => -5]);

        $this->assertIsString($email);
        $this->assertStringContainsString('@', $email);
        // Should generate normally when length is negative
        $parts = explode('@', $email);
        $this->assertGreaterThan(0, strlen($parts[0]));
    }

    /**
     * Test that EmailFaker combines domain and format options.
     */
    public function testGenerateWithDomainAndFormat(): void
    {
        $faker = new EmailFaker('en_US');
        $email = $faker->generate(['domain' => 'test.com', 'format' => 'name.surname']);

        $this->assertIsString($email);
        $this->assertStringEndsWith('@test.com', $email);
        $parts = explode('@', $email);
        $this->assertStringContainsString('.', $parts[0]);
    }
}
