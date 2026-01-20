<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Faker for shuffling values within a column while maintaining distribution.
 *
 * This faker shuffles existing values in a column, preserving statistical
 * properties while anonymizing individual records.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.shuffle')]
#[Autoconfigure(public: true)]
final class ShuffleFaker implements FakerInterface
{
    /**
     * Creates a new ShuffleFaker instance.
     */
    public function __construct() {}

    /**
     * Generates a shuffled value from a pool of existing values.
     *
     * @param array<string, mixed> $options Options:
     *   - 'values' (array<mixed>): Pool of values to shuffle from (required).
     *   - 'seed' (int|null): Optional seed for reproducible shuffling.
     *   - 'exclude' (mixed|null): Value to exclude from selection.
     * @return mixed A randomly selected value from the pool.
     * @throws \InvalidArgumentException If 'values' option is missing or empty.
     */
    public function generate(array $options = []): mixed
    {
        if (!isset($options['values']) || !is_array($options['values']) || empty($options['values'])) {
            throw new \InvalidArgumentException('ShuffleFaker requires a "values" option with a non-empty array of values to shuffle from.');
        }

        $values = $options['values'];
        $seed = $options['seed'] ?? null;
        $exclude = $options['exclude'] ?? null;

        // Filter out excluded value if specified
        if ($exclude !== null) {
            $values = array_filter($values, fn($value) => $value !== $exclude);
            if (empty($values)) {
                throw new \InvalidArgumentException('ShuffleFaker: All values were excluded. At least one value must remain.');
            }
        }

        // Set seed for reproducible shuffling
        if ($seed !== null) {
            mt_srand((int) $seed);
        }

        // Shuffle array
        $shuffled = $values;
        shuffle($shuffled);

        // Reset seed if it was set
        if ($seed !== null) {
            mt_srand(); // Reset to random seed
        }

        // Return first value from shuffled array
        return $shuffled[0];
    }
}
