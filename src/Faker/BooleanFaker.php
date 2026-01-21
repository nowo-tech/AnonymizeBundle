<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized boolean values.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.boolean')]
final class BooleanFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new BooleanFaker instance.
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
     * Generates an anonymized boolean value.
     *
     * @param array<string, mixed> $options Options:
     *   - 'true_probability' (int): Probability of true (0-100, default: 50)
     * @return bool The anonymized boolean value
     */
    public function generate(array $options = []): bool
    {
        $trueProbability = (int) ($options['true_probability'] ?? 50);
        $trueProbability = max(0, min(100, $trueProbability)); // Clamp between 0 and 100

        return $this->faker->boolean($trueProbability);
    }
}
