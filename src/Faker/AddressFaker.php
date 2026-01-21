<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized street addresses.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.address')]
final class AddressFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new AddressFaker instance.
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
     * Generates an anonymized street address.
     *
     * @param array<string, mixed> $options Options:
     *   - 'country' (string): Specific country code (e.g., 'US', 'ES', 'FR')
     *   - 'include_postal_code' (bool): Include postal code in address (default: false)
     *   - 'format' (string): 'full' for full address, 'short' for street only (default: 'full')
     * @return string The anonymized address
     */
    public function generate(array $options = []): string
    {
        $includePostalCode = $options['include_postal_code'] ?? false;
        $format = $options['format'] ?? 'full';
        $country = $options['country'] ?? null;

        if ($country !== null) {
            $this->faker = Factory::create($this->getLocaleForCountry($country));
        }

        if ($format === 'short') {
            return $this->faker->streetAddress();
        }

        $address = $this->faker->streetAddress();

        if ($includePostalCode) {
            $address .= ', ' . $this->faker->postcode();
        }

        $address .= ', ' . $this->faker->city();

        return $address;
    }

    /**
     * Gets the appropriate locale for a country code.
     *
     * @param string $country The country code
     * @return string The locale string
     */
    private function getLocaleForCountry(string $country): string
    {
        $countryLocales = [
            'US' => 'en_US',
            'GB' => 'en_GB',
            'ES' => 'es_ES',
            'FR' => 'fr_FR',
            'DE' => 'de_DE',
            'IT' => 'it_IT',
            'NL' => 'nl_NL',
            'PT' => 'pt_PT',
        ];

        return $countryLocales[strtoupper($country)] ?? 'en_US';
    }
}
