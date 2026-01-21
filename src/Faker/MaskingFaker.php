<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Faker for partial masking of sensitive data.
 *
 * This faker masks parts of a value while preserving some characters,
 * useful for compliance scenarios where partial data visibility is required.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.masking')]
#[Autoconfigure(public: true)]
final class MaskingFaker implements FakerInterface
{
    /**
     * Generates a masked version of the original value.
     *
     * @param array<string, mixed> $options Options:
     *   - 'original_value' (string): The original value to mask (standard, always provided)
     *   - 'value' (string): Alias for 'original_value' (backward compatibility)
     *   - 'preserve_start' (int): Number of characters to preserve at start (default: 1)
     *   - 'preserve_end' (int): Number of characters to preserve at end (default: 0)
     *   - 'mask_char' (string): Character to use for masking (default: '*')
     *   - 'mask_length' (int|null): Fixed length for mask, null for auto (default: null)
     * @return string The masked value
     * @throws \InvalidArgumentException If neither 'original_value' nor 'value' option is provided or is not a string
     */
    public function generate(array $options = []): string
    {
        // Support both 'original_value' (standard) and 'value' (backward compatibility)
        $value = $options['original_value'] ?? $options['value'] ?? null;

        if ($value === null || !is_string($value)) {
            throw new \InvalidArgumentException('MaskingFaker requires an "original_value" (or "value") option with the original value to mask.');
        }
        $preserveStart = (int) ($options['preserve_start'] ?? 1);
        $preserveEnd = (int) ($options['preserve_end'] ?? 0);
        $maskChar = $options['mask_char'] ?? '*';
        $maskLength = $options['mask_length'] ?? null;

        $valueLength = strlen($value);

        // If value is too short, return fully masked
        if ($valueLength <= $preserveStart + $preserveEnd) {
            return str_repeat($maskChar, $valueLength);
        }

        // Calculate mask length
        if ($maskLength !== null) {
            $actualMaskLength = (int) $maskLength;
        } else {
            $actualMaskLength = $valueLength - $preserveStart - $preserveEnd;
        }

        // Build masked value
        $start = substr($value, 0, $preserveStart);
        $mask = str_repeat($maskChar, $actualMaskLength);
        $end = $preserveEnd > 0 ? substr($value, -$preserveEnd) : '';

        return $start . $mask . $end;
    }
}
