<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Faker for generating anonymized usernames.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.username')]
final class UsernameFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new UsernameFaker instance.
     *
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized username.
     *
     * @param array<string, mixed> $options Options:
     *   - 'min_length' (int): Minimum username length (default: 5)
     *   - 'max_length' (int): Maximum username length (default: 20)
     *   - 'prefix' (string): Prefix to add to username
     *   - 'suffix' (string): Suffix to add to username
     *   - 'include_numbers' (bool): Include numbers in username (default: true)
     * @return string The anonymized username
     */
    public function generate(array $options = []): string
    {
        $minLength = (int) ($options['min_length'] ?? 5);
        $maxLength = (int) ($options['max_length'] ?? 20);
        $prefix = $options['prefix'] ?? '';
        $suffix = $options['suffix'] ?? '';
        $includeNumbers = $options['include_numbers'] ?? true;

        // Generate base username
        $base = $this->faker->userName();

        // Adjust length
        $targetLength = $this->faker->numberBetween($minLength, $maxLength);
        $base = substr($base, 0, $targetLength);

        // Add numbers if needed
        if ($includeNumbers && $this->faker->boolean(70)) {
            $base .= (string) $this->faker->numberBetween(0, 999);
        }

        // Apply prefix and suffix
        $username = $prefix . $base . $suffix;

        // Ensure it doesn't exceed max_length
        if (strlen($username) > $maxLength) {
            $username = substr($username, 0, $maxLength);
        }

        return $username;
    }
}
