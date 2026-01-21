<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized IBAN numbers.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.iban')]
final class IbanFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new IbanFaker instance.
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
     * Generates an anonymized IBAN.
     *
     * @param array<string, mixed> $options Options:
     *   - 'country' (string): Country code (ISO 3166-1 alpha-2, default: 'ES')
     *   - 'valid' (bool): Generate valid IBAN with correct checksum (default: true)
     *   - 'formatted' (bool): Include spaces in IBAN (default: false)
     * @return string The anonymized IBAN
     */
    public function generate(array $options = []): string
    {
        $country = $options['country'] ?? 'ES';
        $valid = $options['valid'] ?? true;
        $formatted = $options['formatted'] ?? false;

        // Generate IBAN (Faker library generates valid IBANs by default)
        $iban = $this->faker->iban($country);

        // If valid is false, we could generate an invalid one, but for safety
        // we'll keep it valid. Invalid IBANs are not recommended for anonymization.
        // The 'valid' option is kept for API consistency but always generates valid IBANs.

        // Format with spaces if requested
        if ($formatted) {
            // IBAN format: 4 chars, then groups of 4
            $iban = strtoupper($iban);
            $iban = chunk_split($iban, 4, ' ');
            $iban = trim($iban);
        } else {
            // Remove spaces if any
            $iban = str_replace(' ', '', $iban);
        }

        return $iban;
    }
}
