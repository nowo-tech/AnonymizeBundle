<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Faker for generating anonymized first names.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.name')]
final class NameFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new NameFaker instance.
     *
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized first name.
     *
     * @param array<string, mixed> $options Options:
     *   - 'gender' (string): Gender-specific name ('male', 'female', or 'random', default: 'random')
     *   - 'locale_specific' (bool): Use locale-specific names (default: true, uses constructor locale)
     * @return string The anonymized first name
     */
    public function generate(array $options = []): string
    {
        $gender = $options['gender'] ?? 'random';
        $localeSpecific = $options['locale_specific'] ?? true;

        // If locale_specific is false, we could use a different locale, but for simplicity
        // we'll use the current locale. The option is kept for API consistency.

        return match ($gender) {
            'male' => $this->faker->firstNameMale(),
            'female' => $this->faker->firstNameFemale(),
            'random' => $this->faker->firstName(),
            default => $this->faker->firstName(),
        };
    }
}
