<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

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
     * @param string $locale The locale for Faker generator
     */
    public function __construct(
        #[Autowire('%nowo_anonymize.locale%')]
        string $locale = 'en_US'
    )
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

        // Calculate available length for base username (accounting for prefix and suffix)
        $prefixLength = strlen($prefix);
        $suffixLength = strlen($suffix);
        $availableLength = $maxLength - $prefixLength - $suffixLength;

        // Ensure available length is at least min_length
        if ($availableLength < $minLength) {
            $availableLength = $minLength;
        }

        // Generate base username
        $base = $this->faker->userName();

        // Adjust length to fit within available space
        $targetLength = $this->faker->numberBetween($minLength, min($availableLength, strlen($base)));
        $base = substr($base, 0, $targetLength);

        // Add numbers if needed and there's space
        if ($includeNumbers && $this->faker->boolean(70)) {
            $remainingLength = $availableLength - strlen($base);
            if ($remainingLength > 0) {
                // Limit remainingLength to prevent overflow (max safe value for pow(10, n) as int)
                $safeLength = min($remainingLength, 9); // pow(10, 9) = 1,000,000,000 (safe for int)
                $maxNumber = min(999, (int) pow(10, $safeLength) - 1);
                $base .= (string) $this->faker->numberBetween(0, $maxNumber);
            }
        }

        // Apply prefix and suffix
        $username = $prefix . $base . $suffix;

        // Final length check and adjustment
        if (strlen($username) > $maxLength) {
            $username = substr($username, 0, $maxLength);
        }

        // Ensure minimum length (pad if necessary)
        if (strlen($username) < $minLength) {
            $username = str_pad($username, $minLength, $this->faker->randomLetter());
        }

        return $username;
    }
}
