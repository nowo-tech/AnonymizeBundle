<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Faker for generating anonymized text content (sentences, paragraphs).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.text')]
final class TextFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new TextFaker instance.
     *
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates anonymized text content.
     *
     * @param array<string, mixed> $options Options:
     *   - 'type' (string): 'sentence' or 'paragraph' (default: 'sentence')
     *   - 'min_words' (int): Minimum number of words (default: 5)
     *   - 'max_words' (int): Maximum number of words (default: 20)
     * @return string The anonymized text content
     */
    public function generate(array $options = []): string
    {
        $type = $options['type'] ?? 'sentence';
        $minWords = (int) ($options['min_words'] ?? 5);
        $maxWords = (int) ($options['max_words'] ?? 20);

        return match ($type) {
            'paragraph' => $this->faker->paragraph($this->faker->numberBetween($minWords, $maxWords)),
            'sentence' => $this->faker->sentence($this->faker->numberBetween($minWords, $maxWords)),
            default => $this->faker->sentence($this->faker->numberBetween($minWords, $maxWords)),
        };
    }
}
