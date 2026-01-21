<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized numeric values.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.numeric')]
final class NumericFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new NumericFaker instance.
     *
     * @param string $locale The locale for Faker generator
     */
    public function __construct(
        #[Autowire('%nowo_anonymize.locale%')]
        string $locale = 'en_US'
    )
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized numeric value.
     *
     * @param array<string, mixed> $options Options:
     *   - 'type' (string): Number type ('int', 'float', default: 'int')
     *   - 'min' (int|float): Minimum value (default: 0)
     *   - 'max' (int|float): Maximum value (default: 1000)
     *   - 'precision' (int): Decimal precision for floats (default: 2)
     * @return int|float|string The anonymized numeric value
     */
    public function generate(array $options = []): int|float|string
    {
        $type = $options['type'] ?? 'int';
        $min = $options['min'] ?? 0;
        $max = $options['max'] ?? 1000;
        $precision = (int) ($options['precision'] ?? 2);

        if ($type === 'float') {
            $value = $this->faker->randomFloat($precision, (float) $min, (float) $max);
            return $value;
        }

        return $this->faker->numberBetween((int) $min, (int) $max);
    }
}
