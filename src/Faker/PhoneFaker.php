<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized phone numbers.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.phone')]
final class PhoneFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new PhoneFaker instance.
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
     * Generates an anonymized phone number.
     *
     * @param array<string, mixed> $options Options:
     *                                      - 'country_code' (string): Specific country code (e.g., '+1', '+34')
     *                                      - 'format' (string): 'international' or 'national' (default: 'international')
     *                                      - 'include_extension' (bool): Include extension (default: false)
     *
     * @return string The anonymized phone number
     */
    public function generate(array $options = []): string
    {
        $countryCode      = $options['country_code'] ?? null;
        $format           = $options['format'] ?? 'international';
        $includeExtension = $options['include_extension'] ?? false;

        $phoneNumber = $this->faker->phoneNumber();

        // Apply country code if specified
        if ($countryCode !== null) {
            // Remove existing country code if present
            $phoneNumber = preg_replace('/^\+?\d{1,4}\s*/', '', $phoneNumber);
            $phoneNumber = $countryCode . ' ' . $phoneNumber;
        }

        // Format based on option
        if ($format === 'national') {
            // Remove country code if present
            $phoneNumber = preg_replace('/^\+?\d{1,4}\s*/', '', $phoneNumber);
        }

        // Add extension if requested
        if ($includeExtension) {
            $extension = $this->faker->numberBetween(1000, 9999);
            $phoneNumber .= ' ext. ' . $extension;
        }

        return $phoneNumber;
    }
}
