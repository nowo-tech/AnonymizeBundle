<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;

/**
 * Faker for generating anonymized credit card numbers.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class CreditCardFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new CreditCardFaker instance.
     *
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized credit card number.
     *
     * @param array<string, mixed> $options Additional options (not used for credit card)
     * @return string The anonymized credit card number
     */
    public function generate(array $options = []): string
    {
        return $this->faker->creditCardNumber();
    }
}
