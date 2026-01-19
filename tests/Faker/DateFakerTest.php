<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\DateFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for DateFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class DateFakerTest extends TestCase
{
    /**
     * Test that DateFaker generates a valid date.
     */
    public function testGenerate(): void
    {
        $faker = new DateFaker('en_US');
        $date = $faker->generate();

        $this->assertIsString($date);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date);
    }

    /**
     * Test that DateFaker respects format option.
     */
    public function testGenerateWithFormat(): void
    {
        $faker = new DateFaker('en_US');
        $date = $faker->generate(['format' => 'Y-m-d H:i:s']);

        $this->assertIsString($date);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date);
    }

    /**
     * Test that DateFaker generates past dates.
     */
    public function testGeneratePast(): void
    {
        $faker = new DateFaker('en_US');
        $date = $faker->generate(['type' => 'past']);

        $this->assertIsString($date);
        $dateTime = new \DateTime($date);
        $now = new \DateTime();
        $this->assertLessThanOrEqual($now, $dateTime);
    }

    /**
     * Test that DateFaker generates future dates.
     */
    public function testGenerateFuture(): void
    {
        $faker = new DateFaker('en_US');
        $date = $faker->generate(['type' => 'future', 'max_date' => '+1 year']);

        $this->assertIsString($date);
        $dateTime = new \DateTime($date);
        $now = new \DateTime();
        $this->assertGreaterThanOrEqual($now, $dateTime);
    }

    /**
     * Test that DateFaker respects min_date and max_date options.
     */
    public function testGenerateWithDateRange(): void
    {
        $faker = new DateFaker('en_US');
        $date = $faker->generate(['min_date' => '2020-01-01', 'max_date' => '2020-12-31']);

        $this->assertIsString($date);
        $dateTime = new \DateTime($date);
        $minDate = new \DateTime('2020-01-01');
        $maxDate = new \DateTime('2020-12-31');
        $this->assertGreaterThanOrEqual($minDate, $dateTime);
        $this->assertLessThanOrEqual($maxDate, $dateTime);
    }
}
