<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Faker for generating anonymized JSON structures.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.json')]
final class JsonFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new JsonFaker instance.
     *
     * @param string $locale The locale for Faker generator (default: 'en_US')
     */
    public function __construct(string $locale = 'en_US')
    {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized JSON structure.
     *
     * @param array<string, mixed> $options Options:
     *   - 'schema' (array): Schema definition for JSON structure (optional)
     *   - 'depth' (int): Maximum nesting depth (default: 2)
     *   - 'max_items' (int): Maximum items in arrays (default: 5)
     * @return string The anonymized JSON string
     */
    public function generate(array $options = []): string
    {
        $schema = $options['schema'] ?? null;
        $depth = (int) ($options['depth'] ?? 2);
        $maxItems = (int) ($options['max_items'] ?? 5);

        if ($schema !== null && is_array($schema)) {
            $data = $this->generateFromSchema($schema, $depth);
        } else {
            $data = $this->generateRandomStructure($depth, $maxItems);
        }

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * Generates data from a schema definition.
     *
     * @param array<string, mixed> $schema Schema definition
     * @param int $maxDepth Maximum nesting depth
     * @return array<string, mixed> Generated data
     */
    private function generateFromSchema(array $schema, int $maxDepth): array
    {
        if ($maxDepth <= 0) {
            return [];
        }

        $data = [];
        foreach ($schema as $key => $value) {
            if (is_array($value)) {
                if (isset($value['type'])) {
                    $data[$key] = $this->generateValueByType($value['type'], $maxDepth - 1);
                } else {
                    $data[$key] = $this->generateFromSchema($value, $maxDepth - 1);
                }
            } else {
                $data[$key] = $this->faker->word();
            }
        }

        return $data;
    }

    /**
     * Generates a random JSON structure.
     *
     * @param int $maxDepth Maximum nesting depth
     * @param int $maxItems Maximum items in arrays
     * @return array<string, mixed> Generated data
     */
    private function generateRandomStructure(int $maxDepth, int $maxItems): array
    {
        if ($maxDepth <= 0) {
            return ['value' => $this->faker->word()];
        }

        $structure = [];
        $itemCount = $this->faker->numberBetween(2, $maxItems);

        for ($i = 0; $i < $itemCount; $i++) {
            $key = $this->faker->word();
            $type = $this->faker->randomElement(['string', 'number', 'boolean', 'array', 'object']);

            $structure[$key] = match ($type) {
                'string' => $this->faker->sentence(),
                'number' => $this->faker->randomFloat(2, 0, 1000),
                'boolean' => $this->faker->boolean(),
                'array' => $this->faker->randomElements(['a', 'b', 'c', 'd', 'e'], $this->faker->numberBetween(1, 3)),
                'object' => $this->generateRandomStructure($maxDepth - 1, $maxItems),
                default => $this->faker->word(),
            };
        }

        return $structure;
    }

    /**
     * Generates a value by type.
     *
     * @param string $type Value type
     * @param int $maxDepth Maximum nesting depth
     * @return mixed Generated value
     */
    private function generateValueByType(string $type, int $maxDepth): mixed
    {
        return match ($type) {
            'string' => $this->faker->sentence(),
            'number', 'integer' => $this->faker->numberBetween(0, 1000),
            'float' => $this->faker->randomFloat(2, 0, 1000),
            'boolean' => $this->faker->boolean(),
            'array' => $this->faker->randomElements(['a', 'b', 'c', 'd', 'e'], $this->faker->numberBetween(1, 3)),
            'object' => $maxDepth > 0 ? $this->generateRandomStructure($maxDepth, 3) : [],
            default => $this->faker->word(),
        };
    }
}
