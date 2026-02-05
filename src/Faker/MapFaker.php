<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Faker for replacing values using a mapping: "if value is X, put Y".
 *
 * Use this when you want to anonymize by substituting each original value
 * with a fixed replacement (e.g. status 'active' → 'status_a', 'inactive' → 'status_b').
 * You can define as many pairs as you need.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.map')]
#[Autoconfigure(public: true)]
final class MapFaker implements FakerInterface
{
    /**
     * Creates a new MapFaker instance.
     */
    public function __construct() {}

    /**
     * Returns the replacement value for the original value according to the map.
     *
     * @param array<string, mixed> $options Options:
     *   - 'map' (array): Associative array [ original_value => replacement_value ]. Required.
     *   - 'original_value' (mixed): The current value (always provided by the bundle).
     *   - 'default' (mixed): Value to use when original_value is not in the map. Optional; if omitted and value is not in map, the original value is returned.
     * @return mixed The replacement value from the map, or default/original when not mapped.
     * @throws \InvalidArgumentException If 'map' option is missing or empty.
     */
    public function generate(array $options = []): mixed
    {
        if (!isset($options['map']) || !is_array($options['map']) || empty($options['map'])) {
            throw new \InvalidArgumentException('MapFaker requires a "map" option with a non-empty associative array (original_value => replacement_value).');
        }

        $map = $options['map'];
        $originalValue = $options['original_value'] ?? null;
        $default = $options['default'] ?? null;
        $useDefaultWhenNotMapped = array_key_exists('default', $options);

        if (array_key_exists($originalValue, $map)) {
            return $map[$originalValue];
        }

        return $useDefaultWhenNotMapped ? $default : $originalValue;
    }
}
