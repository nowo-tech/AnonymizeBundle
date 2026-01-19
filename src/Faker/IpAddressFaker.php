<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Faker for generating anonymized IP addresses.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.ip_address')]
final class IpAddressFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new IpAddressFaker instance.
     *
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized IP address.
     *
     * @param array<string, mixed> $options Options:
     *   - 'version' (int): IP version (4 or 6, default: 4)
     *   - 'type' (string): IP type ('public', 'private', 'localhost', default: 'public')
     * @return string The anonymized IP address
     */
    public function generate(array $options = []): string
    {
        $version = (int) ($options['version'] ?? 4);
        $type = $options['type'] ?? 'public';

        if ($version === 6) {
            return $this->generateIpv6($type);
        }

        return $this->generateIpv4($type);
    }

    /**
     * Generates an IPv4 address.
     *
     * @param string $type IP type ('public', 'private', 'localhost')
     * @return string IPv4 address
     */
    private function generateIpv4(string $type): string
    {
        return match ($type) {
            'private' => $this->faker->localIpv4(),
            'localhost' => $this->faker->ipv4(),
            default => $this->faker->ipv4(),
        };
    }

    /**
     * Generates an IPv6 address.
     *
     * @param string $type IP type ('public', 'private', 'localhost')
     * @return string IPv6 address
     */
    private function generateIpv6(string $type): string
    {
        // Generate IPv6 address (8 groups of 4 hex digits)
        $groups = [];
        for ($i = 0; $i < 8; $i++) {
            $groups[] = dechex($this->faker->numberBetween(0, 65535));
        }

        $ipv6 = implode(':', $groups);

        // Handle localhost
        if ($type === 'localhost') {
            return '::1';
        }

        // Handle private (link-local)
        if ($type === 'private') {
            // Link-local addresses start with fe80::
            $groups[0] = 'fe80';
            $groups[1] = '0000';
            return implode(':', $groups);
        }

        return $ipv6;
    }
}
