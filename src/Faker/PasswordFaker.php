<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized passwords.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.password')]
final class PasswordFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new PasswordFaker instance.
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
     * Generates an anonymized password.
     *
     * @param array<string, mixed> $options Options:
     *   - 'length' (int): Password length (default: 12)
     *   - 'include_special' (bool): Include special characters (default: true)
     *   - 'include_numbers' (bool): Include numbers (default: true)
     *   - 'include_uppercase' (bool): Include uppercase letters (default: true)
     * @return string The anonymized password
     */
    public function generate(array $options = []): string
    {
        $length = (int) ($options['length'] ?? 12);
        $includeSpecial = $options['include_special'] ?? true;
        $includeNumbers = $options['include_numbers'] ?? true;
        $includeUppercase = $options['include_uppercase'] ?? true;

        // Define character sets
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        // Build full character set
        $chars = $lowercase;
        if ($includeUppercase) {
            $chars .= $uppercase;
        }
        if ($includeNumbers) {
            $chars .= $numbers;
        }
        if ($includeSpecial) {
            $chars .= $special;
        }

        // Start with required characters to guarantee they are included
        $passwordChars = [];

        if ($includeUppercase) {
            $passwordChars[] = $uppercase[$this->faker->numberBetween(0, strlen($uppercase) - 1)];
        }

        if ($includeNumbers) {
            $passwordChars[] = $numbers[$this->faker->numberBetween(0, strlen($numbers) - 1)];
        }

        if ($includeSpecial) {
            $passwordChars[] = $special[$this->faker->numberBetween(0, strlen($special) - 1)];
        }

        // Fill the rest with random characters from the full set
        $charsLength = strlen($chars);
        $remainingLength = $length - count($passwordChars);

        for ($i = 0; $i < $remainingLength; $i++) {
            $passwordChars[] = $chars[$this->faker->numberBetween(0, $charsLength - 1)];
        }

        // Shuffle to randomize positions
        shuffle($passwordChars);

        return implode('', $passwordChars);
    }
}
