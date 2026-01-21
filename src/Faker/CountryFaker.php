<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized country codes and names.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.country')]
final class CountryFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new CountryFaker instance.
     *
     * @param string $locale The locale for Faker generator
     */
    public function __construct(
        #[Autowire('%nowo_anonymize.locale%')]
        string $locale = 'en_US'
    )
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized country code or name.
     *
     * @param array<string, mixed> $options Options:
     *   - 'format' (string): 'code', 'name', 'iso2', or 'iso3' (default: 'code')
     *   - 'locale' (string): Override locale for country names (optional)
     * @return string The anonymized country value
     */
    public function generate(array $options = []): string
    {
        $format = $options['format'] ?? 'code';
        $locale = $options['locale'] ?? null;

        // Use custom locale if provided
        if ($locale !== null) {
            $this->faker = Factory::create($locale);
        }

        return match ($format) {
            'name' => $this->faker->country(),
            'iso2' => $this->faker->countryCode(),
            'iso3' => $this->faker->countryISOAlpha3(),
            'code' => $this->faker->countryCode(),
            default => $this->faker->countryCode(),
        };
    }
}
