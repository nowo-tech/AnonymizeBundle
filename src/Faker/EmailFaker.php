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
     * @param array<string, mixed> $options Options:
     *   - 'domain' (string): Custom domain to use (default: random)
     *   - 'format' (string): 'name.surname' or 'random' (default: 'random')
     *   - 'local_part_length' (int): Length of local part (default: random)
     * @return string The anonymized email address
     */
    public function generate(array $options = []): string
    {
        $domain = $options['domain'] ?? null;
        $format = $options['format'] ?? 'random';
        $localPartLength = $options['local_part_length'] ?? null;

        // Generate local part based on format
        $localPart = match ($format) {
            'name.surname' => strtolower($this->faker->firstName() . '.' . $this->faker->lastName()),
            'random' => $this->faker->userName(),
            default => $this->faker->userName(),
        };

        // Adjust local part length if specified
        if ($localPartLength !== null && $localPartLength > 0) {
            $localPart = substr($localPart, 0, (int) $localPartLength);
            if (strlen($localPart) < $localPartLength) {
                $localPart .= $this->faker->randomNumber($localPartLength - strlen($localPart), true);
            }
        }

        // Use custom domain or generate random
        $emailDomain = $domain ?? $this->faker->domainName();

        return $localPart . '@' . $emailDomain;
    }
}
