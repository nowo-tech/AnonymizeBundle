<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;

/**
 * Faker for generating anonymized first names.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class NameFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new NameFaker instance.
     *
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized first name.
     *
     * @param array<string, mixed> $options Additional options (not used for name)
     * @return string The anonymized first name
     */
    public function generate(array $options = []): string
    {
        return $this->faker->firstName();
    }
}
