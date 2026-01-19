<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

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
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized IBAN.
     *
     * @param array<string, mixed> $options Options: 'country' (default: 'ES')
     * @return string The anonymized IBAN
     */
    public function generate(array $options = []): string
    {
        $country = $options['country'] ?? 'ES';

        return $this->faker->iban($country);
    }
}
