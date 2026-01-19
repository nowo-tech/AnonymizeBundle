<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Faker for generating anonymized email addresses.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.email')]
final class EmailFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new EmailFaker instance.
     *
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized email address.
     *
     * @param array<string, mixed> $options Additional options (not used for email)
     * @return string The anonymized email address
     */
    public function generate(array $options = []): string
    {
        return $this->faker->unique()->safeEmail();
    }
}
