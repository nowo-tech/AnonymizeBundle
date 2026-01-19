<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Faker for generating values from a predefined enum/list.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.enum')]
final class EnumFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new EnumFaker instance.
     *
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates a value from a predefined list.
     *
     * @param array<string, mixed> $options Options:
     *   - 'values' (array): Array of possible values (required)
     *   - 'weighted' (array): Associative array with values as keys and probabilities as values (optional)
     * @return mixed The selected value from the enum
     * @throws \InvalidArgumentException If values option is not provided or empty
     */
    public function generate(array $options = []): mixed
    {
        if (!isset($options['values']) || !is_array($options['values']) || empty($options['values'])) {
            throw new \InvalidArgumentException('EnumFaker requires a "values" option with a non-empty array of possible values.');
        }

        $values = $options['values'];
        $weighted = $options['weighted'] ?? null;

        // If weighted probabilities are provided, use them
        if ($weighted !== null && is_array($weighted) && !empty($weighted)) {
            return $this->selectWeightedValue($weighted);
        }

        // Otherwise, select randomly from values
        return $this->faker->randomElement($values);
    }

    /**
     * Selects a value based on weighted probabilities.
     *
     * @param array<string|int, float|int> $weighted Associative array with values and their weights
     * @return mixed The selected value
     */
    private function selectWeightedValue(array $weighted): mixed
    {
        $totalWeight = array_sum($weighted);
        $random = $this->faker->randomFloat(2, 0, $totalWeight);

        $cumulative = 0;
        foreach ($weighted as $value => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $value;
            }
        }

        // Fallback to last value
        return array_key_last($weighted);
    }
}
