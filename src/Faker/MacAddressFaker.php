<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized MAC addresses.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.mac_address')]
final class MacAddressFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new MacAddressFaker instance.
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
     * Generates an anonymized MAC address.
     *
     * @param array<string, mixed> $options Options:
     *   - 'separator' (string): Separator between octets ('colon', 'dash', 'none', default: 'colon')
     *   - 'uppercase' (bool): Use uppercase letters (default: true)
     * @return string The anonymized MAC address
     */
    public function generate(array $options = []): string
    {
        $separator = $options['separator'] ?? 'colon';
        $uppercase = $options['uppercase'] ?? true;

        // Generate 6 octets (2 hex digits each)
        $octets = [];
        for ($i = 0; $i < 6; $i++) {
            $octet = dechex($this->faker->numberBetween(0, 255));
            // Pad with leading zero if needed
            $octet = str_pad($octet, 2, '0', STR_PAD_LEFT);
            $octets[] = $uppercase ? strtoupper($octet) : strtolower($octet);
        }

        // Apply separator
        $separatorChar = match ($separator) {
            'dash' => '-',
            'none' => '',
            default => ':',
        };

        return implode($separatorChar, $octets);
    }
}
