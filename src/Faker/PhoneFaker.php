<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

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
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized phone number.
     *
     * @param array<string, mixed> $options Additional options (not used for phone)
     * @return string The anonymized phone number
     */
    public function generate(array $options = []): string
    {
        return $this->faker->phoneNumber();
    }
}
