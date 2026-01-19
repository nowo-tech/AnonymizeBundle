<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Faker for generating anonymized UUIDs.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.uuid')]
final class UuidFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new UuidFaker instance.
     *
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized UUID.
     *
     * @param array<string, mixed> $options Options:
     *   - 'version' (int): UUID version (1 or 4, default: 4)
     *   - 'format' (string): Format ('with_dashes' or 'without_dashes', default: 'with_dashes')
     * @return string The anonymized UUID
     */
    public function generate(array $options = []): string
    {
        $version = (int) ($options['version'] ?? 4);
        $format = $options['format'] ?? 'with_dashes';

        // Generate UUID based on version
        $uuid = match ($version) {
            1 => $this->generateUuidV1(),
            default => $this->faker->uuid(),
        };

        // Apply format
        if ($format === 'without_dashes') {
            $uuid = str_replace('-', '', $uuid);
        }

        return $uuid;
    }

    /**
     * Generates a UUID v1 (time-based).
     *
     * @return string UUID v1
     */
    private function generateUuidV1(): string
    {
        // Generate time-based UUID v1
        // Format: time_low-time_mid-time_high_and_version-clock_seq_and_reserved-clock_seq_node
        // For simplicity, we'll use Faker's uuid but could implement proper v1 generation
        
        // For now, use v4 but could be enhanced to generate proper v1 UUIDs
        return $this->faker->uuid();
    }
}
