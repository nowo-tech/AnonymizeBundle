<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Faker for generating anonymized credit card numbers.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.credit_card')]
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
     * @param array<string, mixed> $options Options:
     *   - 'type' (string): 'visa', 'mastercard', 'amex', or 'random' (default: 'random')
     *   - 'valid' (bool): Generate valid Luhn numbers (default: true)
     *   - 'formatted' (bool): Include spaces/dashes (default: false)
     * @return string The anonymized credit card number
     */
    public function generate(array $options = []): string
    {
        $type = $options['type'] ?? 'random';
        $valid = $options['valid'] ?? true;
        $formatted = $options['formatted'] ?? false;

        // Generate card number based on type
        $cardNumber = match ($type) {
            'visa' => $this->faker->creditCardNumber('Visa'),
            'mastercard' => $this->faker->creditCardNumber('MasterCard'),
            'amex' => $this->faker->creditCardNumber('American Express'),
            'random' => $this->faker->creditCardNumber(),
            default => $this->faker->creditCardNumber(),
        };

        // If valid is false, generate invalid number
        if (!$valid) {
            // Remove last digit and replace with random invalid digit
            $cardNumber = substr($cardNumber, 0, -1) . $this->faker->numberBetween(0, 9);
        }

        // Format with spaces/dashes if requested
        if ($formatted) {
            // Remove existing formatting
            $cardNumber = preg_replace('/[\s-]/', '', $cardNumber);
            // Add spaces every 4 digits
            $cardNumber = chunk_split($cardNumber, 4, ' ');
            $cardNumber = trim($cardNumber);
        }

        return $cardNumber;
    }
}
