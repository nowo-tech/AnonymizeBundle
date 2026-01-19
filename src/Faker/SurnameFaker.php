<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

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
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized surname.
     *
     * @param array<string, mixed> $options Additional options (not used for surname)
     * @return string The anonymized surname
     */
    public function generate(array $options = []): string
    {
        return $this->faker->lastName();
    }
}
