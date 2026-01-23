<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized names with fallback logic.
 *
 * This faker handles cases where an entity has multiple name fields (e.g., 'name' and 'firstname')
 * where one can be nullable. It ensures that if one field has a value and the other is null,
 * a random value is generated for the null field to maintain data consistency.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.name_fallback')]
final class NameFallbackFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new NameFallbackFaker instance.
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
     * Generates an anonymized name with fallback logic.
     *
     * @param array<string, mixed> $options Options:
     *   - 'fallback_field' (string): Name of the related field to check (e.g., 'firstname' or 'name')
     *   - 'record' (array): Full database record to check for related field value (optional, will use if available)
     *   - 'original_value' (mixed): The original value of the current field
     *   - 'gender' (string): Gender-specific name ('male', 'female', or 'random', default: 'random')
     *   - 'locale_specific' (bool): Use locale-specific names (default: true)
     * @return string The anonymized name
     */
    public function generate(array $options = []): string
    {
        $fallbackField = $options['fallback_field'] ?? null;
        $record = $options['record'] ?? [];
        $originalValue = $options['original_value'] ?? null;
        $gender = $options['gender'] ?? 'random';
        $localeSpecific = $options['locale_specific'] ?? true;

        // Get the related field value from the record
        $relatedValue = null;
        if ($fallbackField !== null && !empty($record)) {
            // Try to get from record by field name or column name
            $relatedValue = $record[$fallbackField] ?? null;
            
            // If not found, try common column name variations
            if ($relatedValue === null) {
                $relatedValue = $record[strtolower($fallbackField)] ?? null;
            }
            if ($relatedValue === null) {
                $relatedValue = $record[ucfirst($fallbackField)] ?? null;
            }
        }

        // Determine if we need to generate a value
        $currentValueIsNull = ($originalValue === null || $originalValue === '');
        $relatedValueIsNull = ($relatedValue === null || $relatedValue === '');

        // If current field is null but related field has value, generate a random name
        // This ensures data consistency: if one name field exists, the other should too
        if ($currentValueIsNull && !$relatedValueIsNull) {
            return $this->generateName($gender, $localeSpecific);
        }

        // If current field has value, generate a new anonymized name
        // (normal anonymization behavior)
        if (!$currentValueIsNull) {
            return $this->generateName($gender, $localeSpecific);
        }

        // If both are null, generate a random name
        // (both fields will be anonymized independently)
        return $this->generateName($gender, $localeSpecific);
    }

    /**
     * Generates a name based on gender and locale.
     *
     * @param string $gender The gender ('male', 'female', or 'random')
     * @param bool $localeSpecific Whether to use locale-specific names
     * @return string The generated name
     */
    private function generateName(string $gender, bool $localeSpecific): string
    {
        if (!$localeSpecific) {
            // Use a generic name generator
            return $this->faker->firstName();
        }

        return match (strtolower($gender)) {
            'male' => $this->faker->firstNameMale(),
            'female' => $this->faker->firstNameFemale(),
            default => $this->faker->firstName(),
        };
    }
}
