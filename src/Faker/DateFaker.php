<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized dates.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.date')]
final class DateFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new DateFaker instance.
     *
     * @param string $locale The locale for Faker generator
     */
    public function __construct(
        #[Autowire('%nowo_anonymize.locale%')]
        string $locale = 'en_US'
    ) {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized date.
     *
     * @param array<string, mixed> $options Options:
     *   - 'min_date' (string): Minimum date (Y-m-d format or relative like '-100 years')
     *   - 'max_date' (string): Maximum date (Y-m-d format or relative like 'now')
     *   - 'format' (string): Date format (default: 'Y-m-d')
     *   - 'type' (string): 'past', 'future', or 'between' (default: 'between')
     * @return string The anonymized date
     */
    public function generate(array $options = []): string
    {
        $format = $options['format'] ?? 'Y-m-d';
        $type = $options['type'] ?? 'between';

        $minDate = $this->parseDate($options['min_date'] ?? '-100 years');
        $maxDate = $this->parseDate($options['max_date'] ?? 'now');

        $date = match ($type) {
            'past' => $this->faker->dateTimeBetween($minDate, 'now'),
            'future' => $this->faker->dateTimeBetween('now', $maxDate),
            'between' => $this->faker->dateTimeBetween($minDate, $maxDate),
            default => $this->faker->dateTimeBetween($minDate, $maxDate),
        };

        return $date->format($format);
    }

    /**
     * Parses a date string to a format usable by Faker.
     *
     * @param string $date The date string
     * @return string The parsed date string
     */
    private function parseDate(string $date): string
    {
        // If it's already a relative date string, return as is
        if (str_starts_with($date, '-') || str_starts_with($date, '+') || $date === 'now') {
            return $date;
        }

        // Try to parse as Y-m-d format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // Try to parse as timestamp
        if (is_numeric($date)) {
            return '@' . $date;
        }

        // Default to relative date
        return $date;
    }
}
