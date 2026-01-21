<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized color values.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.color')]
final class ColorFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new ColorFaker instance.
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
     * Generates an anonymized color value.
     *
     * @param array<string, mixed> $options Options:
     *   - 'format' (string): Color format ('hex', 'rgb', 'rgba', default: 'hex')
     *   - 'alpha' (float): Alpha channel value for rgba (0.0-1.0, default: 1.0)
     * @return string The anonymized color value
     */
    public function generate(array $options = []): string
    {
        $format = $options['format'] ?? 'hex';
        $alpha = (float) ($options['alpha'] ?? 1.0);

        return match ($format) {
            'rgb' => sprintf('rgb(%d, %d, %d)', $this->faker->numberBetween(0, 255), $this->faker->numberBetween(0, 255), $this->faker->numberBetween(0, 255)),
            'rgba' => sprintf('rgba(%d, %d, %d, %.2f)', $this->faker->numberBetween(0, 255), $this->faker->numberBetween(0, 255), $this->faker->numberBetween(0, 255), $alpha),
            default => $this->faker->hexColor(),
        };
    }
}
