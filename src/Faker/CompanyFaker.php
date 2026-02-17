<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized company names.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.company')]
final class CompanyFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new CompanyFaker instance.
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
     * Generates an anonymized company name.
     *
     * @param array<string, mixed> $options Options:
     *                                      - 'type' (string): Company type 'corporation', 'llc', 'inc', or null for random
     *                                      - 'suffix' (string): Custom suffix (overrides type)
     *
     * @return string The anonymized company name
     */
    public function generate(array $options = []): string
    {
        $companyName = $this->faker->company();

        $suffix = $options['suffix'] ?? null;
        $type   = $options['type'] ?? null;

        if ($suffix !== null) {
            // Remove existing suffix if any
            $companyName = preg_replace('/\s+(Inc\.?|LLC|Ltd\.?|Corp\.?|Corporation)$/i', '', $companyName);

            return $companyName . ' ' . $suffix;
        }

        if ($type !== null) {
            // Remove existing suffix if any
            $companyName = preg_replace('/\s+(Inc\.?|LLC|Ltd\.?|Corp\.?|Corporation)$/i', '', $companyName);

            $suffixes = match (strtolower($type)) {
                'corporation', 'corp' => 'Corp.',
                'llc'   => 'LLC',
                'inc'   => 'Inc.',
                'ltd'   => 'Ltd.',
                default => null,
            };

            if ($suffixes !== null) {
                return $companyName . ' ' . $suffixes;
            }
        }

        return $companyName;
    }
}
