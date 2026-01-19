<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Faker for generating anonymized language codes and names.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.language')]
final class LanguageFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new LanguageFaker instance.
     *
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized language code or name.
     *
     * @param array<string, mixed> $options Options:
     *   - 'format' (string): 'code' or 'name' (default: 'code')
     *   - 'locale' (string): Override locale for language names (optional)
     * @return string The anonymized language value
     */
    public function generate(array $options = []): string
    {
        $format = $options['format'] ?? 'code';
        $locale = $options['locale'] ?? null;

        // Use custom locale if provided
        if ($locale !== null) {
            $this->faker = Factory::create($locale);
        }

        return match ($format) {
            'name' => $this->faker->languageCode() . ' (name)',
            'code' => $this->faker->languageCode(),
            default => $this->faker->languageCode(),
        };
    }
}
