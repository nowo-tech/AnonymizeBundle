<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Metadata;

use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

use function ltrim;

/**
 * Default {@see AnonymizeMetadataProviderInterface}: the `nowo_anonymize.entities` configuration (since 1.5).
 *
 * Immutable: the configuration is set once at container build and value objects are created per call, so nothing
 * leaks between runs in a kept-alive worker.
 *
 * @phpstan-type PropertyConfig array{type: string, weight?: int|null, include_patterns?: array<mixed>, exclude_patterns?: array<mixed>, service?: string|null, options?: array<string, mixed>}
 * @phpstan-type EntityConfig array{connection?: string|null, include_patterns?: array<mixed>, exclude_patterns?: array<mixed>, anonymize_service?: string|null, truncate?: bool, truncate_order?: int|null, mark_anonymized?: bool, properties?: array<string, PropertyConfig>}
 *
 * @since 1.5.0
 */
final readonly class ConfigAnonymizeMetadataProvider implements AnonymizeMetadataProviderInterface
{
    /** @var array<string, EntityConfig> */
    private array $entities;

    /**
     * @param array<string, EntityConfig> $entities keyed by entity FQCN
     */
    public function __construct(array $entities = [])
    {
        $normalised = [];
        foreach ($entities as $className => $config) {
            $normalised[ltrim($className, '\\')] = $config;
        }

        $this->entities = $normalised;
    }

    public function getEntity(string $className): ?Anonymize
    {
        $config = $this->entities[$className] ?? null;
        if ($config === null) {
            return null;
        }

        /** @var array<string, list<string>|string>|list<array<string, list<string>|string>> $include */
        $include = $config['include_patterns'] ?? [];
        /** @var array<string, list<string>|string>|list<array<string, list<string>|string>> $exclude */
        $exclude = $config['exclude_patterns'] ?? [];

        return new Anonymize(
            connection: $config['connection'] ?? null,
            includePatterns: $include,
            excludePatterns: $exclude,
            anonymizeService: $config['anonymize_service'] ?? null,
            truncate: $config['truncate'] ?? false,
            truncate_order: $config['truncate_order'] ?? null,
        );
    }

    public function getProperties(string $className): array
    {
        $properties = [];
        foreach ($this->entities[$className]['properties'] ?? [] as $field => $config) {
            /** @var array<string, list<string>|string>|list<array<string, list<string>|string>> $include */
            $include = $config['include_patterns'] ?? [];
            /** @var array<string, list<string>|string>|list<array<string, list<string>|string>> $exclude */
            $exclude = $config['exclude_patterns'] ?? [];

            $properties[$field] = new AnonymizeProperty(
                type: $config['type'],
                weight: $config['weight'] ?? null,
                includePatterns: $include,
                excludePatterns: $exclude,
                service: $config['service'] ?? null,
                options: $config['options'] ?? [],
            );
        }

        return $properties;
    }

    public function marksAnonymized(string $className): bool
    {
        return $this->entities[$className]['mark_anonymized'] ?? false;
    }
}
