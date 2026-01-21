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

        // Build character set
        $chars = 'abcdefghijklmnopqrstuvwxyz';

        if ($includeUppercase) {
            $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        if ($includeNumbers) {
            $chars .= '0123456789';
        }

        if ($includeSpecial) {
            $chars .= '!@#$%^&*()_+-=[]{}|;:,.<>?';
        }

        // Generate password
        $password = '';
        $charsLength = strlen($chars);

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[$this->faker->numberBetween(0, $charsLength - 1)];
        }

        return $password;
    }
}
