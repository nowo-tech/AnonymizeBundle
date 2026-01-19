<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;

/**
 * Faker for generating anonymized ages.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AgeFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new AgeFaker instance.
     *
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized age.
     *
     * @param array<string, mixed> $options Options: 'min' (default: 18), 'max' (default: 100)
     * @return int The anonymized age
     */
    public function generate(array $options = []): int
    {
        $min = $options['min'] ?? 18;
        $max = $options['max'] ?? 100;

        return $this->faker->numberBetween((int) $min, (int) $max);
    }
}
