<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized URLs and domains.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.url')]
final class UrlFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new UrlFaker instance.
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
     * Generates an anonymized URL.
     *
     * @param array<string, mixed> $options Options:
     *                                      - 'scheme' (string): URL scheme 'http' or 'https' (default: 'https')
     *                                      - 'domain' (string): Specific domain to use
     *                                      - 'path' (bool): Include path in URL (default: true)
     *
     * @return string The anonymized URL
     */
    public function generate(array $options = []): string
    {
        $scheme      = $options['scheme'] ?? 'https';
        $domain      = $options['domain'] ?? null;
        $includePath = $options['path'] ?? true;

        if ($domain !== null) {
            $url = $scheme . '://' . $domain;
        } else {
            $url = $this->faker->url();
            // Replace scheme if needed
            if ($scheme !== 'https') {
                $url = str_replace('https://', $scheme . '://', $url);
            }
        }

        if (!$includePath) {
            // Remove path, keep only domain
            $parsed = parse_url($url);
            if ($parsed !== false && isset($parsed['scheme'], $parsed['host'])) {
                $url = $parsed['scheme'] . '://' . $parsed['host'];
            }
        }

        return $url;
    }
}
