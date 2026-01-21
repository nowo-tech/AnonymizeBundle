<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Faker for deterministic anonymization using hash functions.
 *
 * This faker hashes the original value to maintain referential integrity
 * while anonymizing data. The same input will always produce the same output.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.hash_preserve')]
#[Autoconfigure(public: true)]
final class HashPreserveFaker implements FakerInterface
{
    /**
     * Creates a new HashPreserveFaker instance.
     */
    public function __construct() {}

    /**
     * Generates a deterministic hash from the original value.
     *
     * @param array<string, mixed> $options Options:
     *   - 'original_value' (mixed): The original value to hash (standard, always provided).
     *   - 'value' (mixed): Alias for 'original_value' (backward compatibility).
     *   - 'algorithm' (string): Hash algorithm ('md5', 'sha1', 'sha256', 'sha512', default: 'sha256').
     *   - 'salt' (string): Optional salt to add before hashing (default: '').
     *   - 'preserve_format' (bool): If true, attempts to preserve the format of the original value (default: false).
     *   - 'length' (int|null): Maximum length of the output (truncates hash if specified).
     * @return string The hashed value.
     * @throws \InvalidArgumentException If neither 'original_value' nor 'value' option is provided.
     */
    public function generate(array $options = []): string
    {
        // Support both 'original_value' (standard) and 'value' (backward compatibility)
        $value = $options['original_value'] ?? $options['value'] ?? null;

        if ($value === null) {
            throw new \InvalidArgumentException('HashPreserveFaker requires an "original_value" (or "value") option with the original value to hash.');
        }
        $algorithm = $options['algorithm'] ?? 'sha256';
        $salt = $options['salt'] ?? '';
        $preserveFormat = $options['preserve_format'] ?? false;
        $length = $options['length'] ?? null;

        // Convert to string
        $valueToHash = (string) $value;

        // Add salt if provided
        if ($salt !== '') {
            $valueToHash = $valueToHash . $salt;
        }

        // Generate hash
        $hash = match ($algorithm) {
            'md5' => md5($valueToHash),
            'sha1' => sha1($valueToHash),
            'sha256' => hash('sha256', $valueToHash),
            'sha512' => hash('sha512', $valueToHash),
            default => hash('sha256', $valueToHash),
        };

        // Truncate if length specified
        if ($length !== null && $length > 0) {
            $hash = substr($hash, 0, (int) $length);
        }

        // Preserve format if requested
        if ($preserveFormat && is_numeric($value)) {
            // If original was numeric, try to make hash numeric-like
            $hash = preg_replace('/[^0-9]/', '', $hash);
            if (empty($hash)) {
                $hash = '0';
            }
            // Limit to reasonable length for numeric
            if (strlen($hash) > 20) {
                $hash = substr($hash, 0, 20);
            }
        }

        return $hash;
    }
}
