<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized hash values.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.hash')]
final class HashFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new HashFaker instance.
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
     * Generates an anonymized hash value.
     *
     * @param array<string, mixed> $options Options:
     *                                      - 'algorithm' (string): Hash algorithm ('md5', 'sha1', 'sha256', 'sha512', default: 'sha256')
     *                                      - 'length' (int|null): Fixed length for hash (null for algorithm default)
     *
     * @return string The anonymized hash value
     */
    public function generate(array $options = []): string
    {
        $algorithm = strtolower($options['algorithm'] ?? 'sha256');
        $length    = $options['length'] ?? null;

        // Generate random string to hash
        $randomString = $this->faker->unique()->text(100) . (string) $this->faker->randomNumber(9, true);

        // Generate hash based on algorithm
        $hash = match ($algorithm) {
            'md5'    => md5($randomString),
            'sha1'   => sha1($randomString),
            'sha256' => hash('sha256', $randomString),
            'sha512' => hash('sha512', $randomString),
            default  => hash('sha256', $randomString),
        };

        // Apply length if specified
        if ($length !== null && $length > 0) {
            $hash = substr($hash, 0, (int) $length);
        }

        return $hash;
    }
}
