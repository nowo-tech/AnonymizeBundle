<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized ages.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.age')]
final class AgeFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new AgeFaker instance.
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
     * Generates an anonymized age.
     *
     * @param array<string, mixed> $options Options:
     *   - 'min' (int): Minimum age (default: 18)
     *   - 'max' (int): Maximum age (default: 100)
     *   - 'distribution' (string): Distribution type ('uniform' or 'normal', default: 'uniform')
     *   - 'mean' (float): Mean age for normal distribution (default: 40)
     *   - 'std_dev' (float): Standard deviation for normal distribution (default: 15)
     * @return int The anonymized age
     */
    public function generate(array $options = []): int
    {
        $min = (int) ($options['min'] ?? 18);
        $max = (int) ($options['max'] ?? 100);
        $distribution = $options['distribution'] ?? 'uniform';
        $mean = (float) ($options['mean'] ?? 40);
        $stdDev = (float) ($options['std_dev'] ?? 15);

        if ($distribution === 'normal') {
            // Generate age using normal distribution (Box-Muller transform)
            // Generate two independent uniform random numbers (avoid 0 and 1)
            $u1 = max(0.0001, min(0.9999, $this->faker->randomFloat(10, 0.0001, 0.9999)));
            $u2 = $this->faker->randomFloat(10, 0, 1);

            // Box-Muller transform to get normal distribution
            $z0 = sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);

            // Apply mean and standard deviation
            $age = (int) round($mean + $z0 * $stdDev);

            // Clamp to min/max bounds
            $age = max($min, min($max, $age));

            return $age;
        }

        // Uniform distribution (default)
        return $this->faker->numberBetween($min, $max);
    }
}
