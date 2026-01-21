<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized file paths and names.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.file')]
final class FileFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new FileFaker instance.
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
     * Generates an anonymized file path or name.
     *
     * @param array<string, mixed> $options Options:
     *   - 'extension' (string): File extension (default: random)
     *   - 'directory' (string): Directory path (default: random)
     *   - 'absolute' (bool): Return absolute path (default: false)
     * @return string The anonymized file path or name
     */
    public function generate(array $options = []): string
    {
        $extension = $options['extension'] ?? $this->faker->fileExtension();
        $directory = $options['directory'] ?? null;
        $absolute = $options['absolute'] ?? false;

        // Generate filename
        $filename = $this->faker->word() . '.' . ltrim($extension, '.');

        // If directory is provided, build path
        if ($directory !== null) {
            $path = rtrim($directory, '/') . '/' . $filename;

            // Make absolute if requested
            if ($absolute && !str_starts_with($path, '/')) {
                $path = '/' . $path;
            }

            return $path;
        }

        // Return just filename or absolute path
        if ($absolute) {
            return '/' . $this->faker->word() . '/' . $filename;
        }

        return $filename;
    }
}
