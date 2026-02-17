<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized surnames.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.surname')]
final class SurnameFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new SurnameFaker instance.
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
     * Generates an anonymized surname.
     *
     * @param array<string, mixed> $options Options:
     *                                      - 'gender' (string): Gender-specific surname if available ('male', 'female', or 'random', default: 'random')
     *                                      - 'locale_specific' (bool): Use locale-specific surnames (default: true, uses constructor locale)
     *
     * @return string The anonymized surname
     */
    public function generate(array $options = []): string
    {
        $gender         = $options['gender'] ?? 'random';
        $localeSpecific = $options['locale_specific'] ?? true;

        // Note: Faker library doesn't have gender-specific surnames in most locales
        // The gender option is kept for API consistency but may not have effect
        // If locale_specific is false, we could use a different locale, but for simplicity
        // we'll use the current locale. The option is kept for API consistency.

        return $this->faker->lastName();
    }
}
