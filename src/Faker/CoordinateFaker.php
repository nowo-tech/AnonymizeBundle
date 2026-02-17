<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Faker for generating anonymized GPS coordinates.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.coordinate')]
final class CoordinateFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new CoordinateFaker instance.
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
     * Generates anonymized GPS coordinates.
     *
     * @param array<string, mixed> $options Options:
     *                                      - 'format' (string): Output format ('array', 'string', 'json', default: 'string')
     *                                      - 'precision' (int): Decimal precision (default: 6)
     *                                      - 'min_lat' (float): Minimum latitude (default: -90.0)
     *                                      - 'max_lat' (float): Maximum latitude (default: 90.0)
     *                                      - 'min_lng' (float): Minimum longitude (default: -180.0)
     *                                      - 'max_lng' (float): Maximum longitude (default: 180.0)
     *
     * @return array<string, float>|string The anonymized coordinates
     */
    public function generate(array $options = []): string|array
    {
        $format    = $options['format'] ?? 'string';
        $precision = (int) ($options['precision'] ?? 6);
        $minLat    = (float) ($options['min_lat'] ?? -90.0);
        $maxLat    = (float) ($options['max_lat'] ?? 90.0);
        $minLng    = (float) ($options['min_lng'] ?? -180.0);
        $maxLng    = (float) ($options['max_lng'] ?? 180.0);

        // Generate coordinates
        $latitude  = round($this->faker->latitude($minLat, $maxLat), $precision);
        $longitude = round($this->faker->longitude($minLng, $maxLng), $precision);

        return match ($format) {
            'array' => ['latitude' => $latitude, 'longitude' => $longitude],
            'json'  => json_encode(['latitude' => $latitude, 'longitude' => $longitude], JSON_THROW_ON_ERROR),
            default => sprintf('%.' . $precision . 'f,%.' . $precision . 'f', $latitude, $longitude),
        };
    }
}
