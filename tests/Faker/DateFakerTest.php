<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use DateTime;
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
        $date  = $faker->generate();

        $this->assertIsString($date);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date);
    }

    /**
     * Test that DateFaker respects format option.
     */
    public function testGenerateWithFormat(): void
    {
        $faker = new DateFaker('en_US');
        $date  = $faker->generate(['format' => 'Y-m-d H:i:s']);

        $this->assertIsString($date);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date);
    }

    /**
     * Test that DateFaker generates past dates.
     */
    public function testGeneratePast(): void
    {
        $faker = new DateFaker('en_US');
        $date  = $faker->generate(['type' => 'past']);

        $this->assertIsString($date);
        $dateTime = new DateTime($date);
        $now      = new DateTime();
        $this->assertLessThanOrEqual($now, $dateTime);
    }

    /**
     * Test that DateFaker generates future dates.
     * Compare by date string (Y-m-d) so "today" is valid when the type is "future"
     * (output format is Y-m-d so time is 00:00:00 and can be before "now" on the same day).
     */
    public function testGenerateFuture(): void
    {
        $faker = new DateFaker('en_US');
        $date  = $faker->generate(['type' => 'future', 'max_date' => '+1 year']);

        $this->assertIsString($date);
        $todayStr = (new DateTime())->format('Y-m-d');
        $this->assertGreaterThanOrEqual($todayStr, $date, 'Generated future date must be today or later');
    }

    /**
     * Test that DateFaker respects min_date and max_date options.
     */
    public function testGenerateWithDateRange(): void
    {
        $faker = new DateFaker('en_US');
        $date  = $faker->generate(['min_date' => '2020-01-01', 'max_date' => '2020-12-31']);

        $this->assertIsString($date);
        $dateTime = new DateTime($date);
        $minDate  = new DateTime('2020-01-01');
        $maxDate  = new DateTime('2020-12-31');
        $this->assertGreaterThanOrEqual($minDate, $dateTime);
        $this->assertLessThanOrEqual($maxDate, $dateTime);
    }

    /**
     * Test that DateFaker handles timestamp format in min_date.
     */
    public function testGenerateWithTimestampMinDate(): void
    {
        $faker     = new DateFaker('en_US');
        $timestamp = (string) strtotime('2020-01-01');
        $date      = $faker->generate(['min_date' => $timestamp, 'max_date' => '2020-12-31']);

        $this->assertIsString($date);
        $dateTime = new DateTime($date);
        $minDate  = new DateTime('@' . $timestamp);
        $maxDate  = new DateTime('2020-12-31');
        $this->assertGreaterThanOrEqual($minDate, $dateTime);
        $this->assertLessThanOrEqual($maxDate, $dateTime);
    }

    /**
     * Test that DateFaker handles relative date strings.
     */
    public function testGenerateWithRelativeDates(): void
    {
        $faker = new DateFaker('en_US');
        $date  = $faker->generate(['min_date' => '-50 years', 'max_date' => '-10 years']);

        $this->assertIsString($date);
        $dateTime = new DateTime($date);
        $now      = new DateTime();
        $this->assertLessThan($now, $dateTime);
    }

    /**
     * Test that DateFaker handles 'now' as max_date.
     */
    public function testGenerateWithNowAsMaxDate(): void
    {
        $faker = new DateFaker('en_US');
        $date  = $faker->generate(['min_date' => '-1 year', 'max_date' => 'now']);

        $this->assertIsString($date);
        $dateTime = new DateTime($date);
        $now      = new DateTime();
        $this->assertLessThanOrEqual($now, $dateTime);
    }

    /**
     * Test that DateFaker handles invalid type gracefully.
     */
    public function testGenerateWithInvalidType(): void
    {
        $faker = new DateFaker('en_US');
        $date  = $faker->generate(['type' => 'invalid_type']);

        $this->assertIsString($date);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date);
    }

    /**
     * Test that DateFaker handles custom format with datetime.
     */
    public function testGenerateWithCustomDateTimeFormat(): void
    {
        $faker = new DateFaker('en_US');
        $date  = $faker->generate(['format' => 'Y-m-d H:i:s', 'type' => 'between']);

        $this->assertIsString($date);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date);
    }

    /**
     * Test that DateFaker handles parseDate with numeric timestamp string.
     */
    public function testGenerateWithNumericTimestampString(): void
    {
        $faker     = new DateFaker('en_US');
        $timestamp = (string) strtotime('2020-06-15');
        $date      = $faker->generate(['min_date' => $timestamp, 'max_date' => $timestamp]);

        $this->assertIsString($date);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date);
    }

    /**
     * Test that DateFaker handles parseDate with relative date starting with +.
     */
    public function testGenerateWithRelativeDatePlus(): void
    {
        $faker = new DateFaker('en_US');
        $date  = $faker->generate(['min_date' => '+1 day', 'max_date' => '+1 year']);

        $this->assertIsString($date);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date);
    }

    /**
     * Test that DateFaker handles parseDate with invalid date format (defaults to relative).
     */
    public function testGenerateWithInvalidDateFormat(): void
    {
        $faker = new DateFaker('en_US');
        $date  = $faker->generate(['min_date' => 'invalid-date-format', 'max_date' => 'now']);

        $this->assertIsString($date);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date);
    }

    /**
     * Test that DateFaker handles parseDate with empty string (defaults to relative).
     */
    public function testGenerateWithEmptyDateString(): void
    {
        $faker = new DateFaker('en_US');
        $date  = $faker->generate(['min_date' => '', 'max_date' => 'now']);

        $this->assertIsString($date);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date);
    }
}
