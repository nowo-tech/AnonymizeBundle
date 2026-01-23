<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized values based on patterns from other fields.
 *
 * This faker allows you to construct values based on:
 * - The anonymized value of another field (e.g., email)
 * - A pattern extracted from the original value of the current field (e.g., number in parentheses)
 *
 * Perfect for cases where fields are derived from other fields but need to preserve certain patterns.
 *
 * Example: username = email + pattern from original username
 * - Original: email = "hola@pepe.com", username = "hola@pepe.com(15)"
 * - Anonymized: email = "john@example.com", username = "john@example.com(15)"
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.pattern_based')]
final class PatternBasedFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new PatternBasedFaker instance.
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
     * Generates an anonymized value based on a pattern from another field and the original value.
     *
     * @param array<string, mixed> $options Options:
     *   - 'source_field' (string, required): Name of the field to use as base (e.g., 'email')
     *   - 'record' (array, required): Full database record including anonymized values
     *   - 'original_value' (string|null): The original value of the current field
     *   - 'pattern' (string): Regex pattern to extract from original_value (default: '/(\\(\\d+\\))$/' for parentheses with number)
     *   - 'pattern_replacement' (string): Replacement pattern for extracted value (default: '$1' to keep as-is)
     *   - 'separator' (string): Separator between source field and pattern (default: '')
     *   - 'fallback_faker' (string): Faker type to use if source field is null (default: 'username')
     *   - 'fallback_options' (array): Options for fallback faker (default: [])
     * @return string The anonymized value
     * @throws \InvalidArgumentException If required options are missing
     */
    public function generate(array $options = []): string
    {
        $sourceField = $options['source_field'] ?? null;
        $record = $options['record'] ?? [];
        $originalValue = $options['original_value'] ?? null;
        $pattern = $options['pattern'] ?? '/(\\(\\d+\\))$/'; // Default: extract (number) at the end
        $patternReplacement = $options['pattern_replacement'] ?? '$1'; // Default: keep extracted pattern
        $separator = $options['separator'] ?? '';
        $fallbackFaker = $options['fallback_faker'] ?? 'username';
        $fallbackOptions = $options['fallback_options'] ?? [];

        if ($sourceField === null) {
            throw new \InvalidArgumentException('PatternBasedFaker requires a "source_field" option.');
        }

        if (empty($record)) {
            throw new \InvalidArgumentException('PatternBasedFaker requires a "record" option with the full database record.');
        }

        // Get the source field value from the record (this will be the anonymized value if already processed)
        $sourceValue = $this->getFieldValue($record, $sourceField);

        // Extract pattern from original value
        $extractedPattern = $this->extractPattern($originalValue, $pattern, $patternReplacement);

        // If source field has a value, use it + extracted pattern
        if ($sourceValue !== null && $sourceValue !== '') {
            return $sourceValue . $separator . $extractedPattern;
        }

        // If source field is null, use fallback faker
        $fakerFactory = new FakerFactory($this->faker->locale);
        $fallbackFakerInstance = $fakerFactory->create($fallbackFaker);
        $fallbackValue = $fallbackFakerInstance->generate($fallbackOptions);

        return $fallbackValue . $separator . $extractedPattern;
    }

    /**
     * Gets a field value from the record, trying different variations.
     *
     * @param array<string, mixed> $record The database record
     * @param string $fieldName The field name to look for
     * @return mixed The field value or null if not found
     */
    private function getFieldValue(array $record, string $fieldName): mixed
    {
        // Try exact match first
        if (isset($record[$fieldName])) {
            return $record[$fieldName];
        }

        // Try lowercase
        if (isset($record[strtolower($fieldName)])) {
            return $record[strtolower($fieldName)];
        }

        // Try uppercase
        if (isset($record[strtoupper($fieldName)])) {
            return $record[strtoupper($fieldName)];
        }

        // Try ucfirst
        if (isset($record[ucfirst($fieldName)])) {
            return $record[ucfirst($fieldName)];
        }

        return null;
    }

    /**
     * Extracts a pattern from the original value.
     *
     * @param mixed $originalValue The original value
     * @param string $pattern The regex pattern to match
     * @param string $replacement The replacement pattern
     * @return string The extracted pattern or empty string if not found
     */
    private function extractPattern(mixed $originalValue, string $pattern, string $replacement): string
    {
        if ($originalValue === null || !is_string($originalValue)) {
            return '';
        }

        // Try to match the pattern
        if (preg_match($pattern, $originalValue, $matches)) {
            // Apply replacement (supports $1, $2, etc. for captured groups)
            $result = $replacement;
            for ($i = 1; $i < count($matches); $i++) {
                $result = str_replace('$' . $i, $matches[$i], $result);
            }
            // Replace $0 with full match if needed
            $result = str_replace('$0', $matches[0], $result);
            return $result;
        }

        return '';
    }
}
