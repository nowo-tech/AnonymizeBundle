<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Faker for copying values from other fields.
 *
 * This faker allows you to copy the anonymized value from another field.
 * Perfect for cases where multiple fields should have the same anonymized value
 * (e.g., email and emailCanonical, username and usernameCanonical).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.copy')]
final class CopyFaker implements FakerInterface
{
    /**
     * Generates an anonymized value by copying from another field.
     *
     * @param array<string, mixed> $options Options:
     *   - 'source_field' (string, required): Name of the field to copy from (e.g., 'email')
     *   - 'record' (array, required): Full database record including anonymized values
     *   - 'fallback_faker' (string): Faker type to use if source field is null (default: 'email')
     *   - 'fallback_options' (array): Options for fallback faker (default: [])
     * @return mixed The copied value (same type as source field)
     * @throws \InvalidArgumentException If required options are missing
     */
    public function generate(array $options = []): mixed
    {
        $sourceField = $options['source_field'] ?? null;
        $record = $options['record'] ?? [];
        $fallbackFaker = $options['fallback_faker'] ?? 'email';
        $fallbackOptions = $options['fallback_options'] ?? [];

        if ($sourceField === null) {
            throw new \InvalidArgumentException('CopyFaker requires a "source_field" option.');
        }

        if (empty($record)) {
            throw new \InvalidArgumentException('CopyFaker requires a "record" option with the full database record.');
        }

        // Get the source field value from the record (this will be the anonymized value if already processed)
        $sourceValue = $this->getFieldValue($record, $sourceField);

        // If source field has a value, return it
        if ($sourceValue !== null && $sourceValue !== '') {
            return $sourceValue;
        }

        // If source field is null, use fallback faker
        $fakerFactory = new FakerFactory();
        $fallbackFakerInstance = $fakerFactory->create($fallbackFaker);
        return $fallbackFakerInstance->generate($fallbackOptions);
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
}
