<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Event\AfterEntityAnonymizeEvent;
use Nowo\AnonymizeBundle\Event\AfterAnonymizeEvent;
use Nowo\AnonymizeBundle\Event\AnonymizePropertyEvent;
use Nowo\AnonymizeBundle\Event\BeforeAnonymizeEvent;
use Nowo\AnonymizeBundle\Event\BeforeEntityAnonymizeEvent;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Faker\FakerInterface;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Service for anonymizing database records.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AnonymizeService
{
    /**
     * @var array<string, FakerInterface> Cache of faker instances
     */
    private array $fakerCache = [];

    /**
     * Creates a new AnonymizeService instance.
     *
     * @param FakerFactory $fakerFactory The faker factory for creating faker instances
     * @param PatternMatcher $patternMatcher The pattern matcher for inclusion/exclusion patterns
     * @param EventDispatcherInterface|null $eventDispatcher Optional event dispatcher for extensibility
     */
    public function __construct(
        private FakerFactory $fakerFactory,
        private PatternMatcher $patternMatcher,
        private ?EventDispatcherInterface $eventDispatcher = null
    ) {}

    /**
     * Gets all entities from the entity manager that have the Anonymize attribute.
     *
     * @param EntityManagerInterface $em The entity manager
     * @return array<string, array{metadata: ClassMetadata, reflection: ReflectionClass, attribute: Anonymize}> Array of entities with their metadata
     */
    public function getAnonymizableEntities(EntityManagerInterface $em): array
    {
        $entities = [];
        $metadataDriver = $em->getConfiguration()->getMetadataDriverImpl();

        if (null === $metadataDriver) {
            return $entities;
        }

        $classNames = $metadataDriver->getAllClassNames();

        foreach ($classNames as $className) {
            try {
                $metadata = $em->getClassMetadata($className);
                $reflection = new ReflectionClass($className);

                $attributes = $reflection->getAttributes(Anonymize::class);
                if (empty($attributes)) {
                    continue;
                }

                $attribute = $attributes[0]->newInstance();
                $entities[$className] = [
                    'metadata' => $metadata,
                    'reflection' => $reflection,
                    'attribute' => $attribute,
                ];
            } catch (\Exception $e) {
                // Skip entities that can't be loaded
                continue;
            }
        }

        return $entities;
    }

    /**
     * Gets all properties from an entity that have the AnonymizeProperty attribute.
     *
     * @param ReflectionClass $reflection The entity reflection class
     * @return array<string, array{property: ReflectionProperty, attribute: AnonymizeProperty, weight: int}> Array of properties with their attributes and weights
     */
    public function getAnonymizableProperties(ReflectionClass $reflection): array
    {
        $properties = [];
        $propertiesWithoutWeight = [];

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(AnonymizeProperty::class);
            if (empty($attributes)) {
                continue;
            }

            $attribute = $attributes[0]->newInstance();
            $weight = $attribute->weight ?? PHP_INT_MAX;

            $propertyData = [
                'property' => $property,
                'attribute' => $attribute,
                'weight' => $weight,
            ];

            if ($weight === PHP_INT_MAX) {
                $propertiesWithoutWeight[] = $propertyData;
            } else {
                $properties[] = $propertyData;
            }
        }

        // Sort by weight
        usort($properties, fn($a, $b) => $a['weight'] <=> $b['weight']);

        // Sort properties without weight alphabetically
        usort($propertiesWithoutWeight, fn($a, $b) => $a['property']->getName() <=> $b['property']->getName());

        // Append properties without weight at the end
        return array_merge($properties, $propertiesWithoutWeight);
    }

    /**
     * Anonymizes records for a given entity.
     *
     * @param EntityManagerInterface $em The entity manager
     * @param ClassMetadata $metadata The entity metadata
     * @param ReflectionClass $reflection The entity reflection class
     * @param array<string, array{property: ReflectionProperty, attribute: AnonymizeProperty, weight: int}> $properties The properties to anonymize
     * @param int $batchSize The batch size for processing
     * @param bool $dryRun If true, only show what would be anonymized
     * @param AnonymizeStatistics|null $statistics Optional statistics collector
     * @param callable|null $progressCallback Optional progress callback (current, total, message)
     * @param Anonymize|null $entityAttribute Optional entity-level Anonymize attribute for filtering records
     * @return array{processed: int, updated: int, propertyStats: array<string, int>} Statistics about the anonymization
     */
    public function anonymizeEntity(
        EntityManagerInterface $em,
        ClassMetadata $metadata,
        ReflectionClass $reflection,
        array $properties,
        int $batchSize = 100,
        bool $dryRun = false,
        ?AnonymizeStatistics $statistics = null,
        ?callable $progressCallback = null,
        ?Anonymize $entityAttribute = null
    ): array {
        $processed = 0;
        $updated = 0;
        $propertyStats = [];
        $tableName = $metadata->getTableName();
        $connection = $em->getConnection();

        // Get all records
        $query = sprintf('SELECT * FROM %s', $connection->quoteSingleIdentifier($tableName));
        $records = $connection->fetchAllAssociative($query);
        $totalRecords = count($records);

        if ($progressCallback !== null && $totalRecords > 0) {
            $progressCallback(0, $totalRecords, sprintf('Starting anonymization of %d records', $totalRecords));
        }

        foreach ($records as $index => $record) {
            $processed++;
            
            // Check entity-level inclusion/exclusion patterns first
            if ($entityAttribute !== null) {
                if (!$this->patternMatcher->matches($record, $entityAttribute->includePatterns, $entityAttribute->excludePatterns)) {
                    // Skip this record if it doesn't match entity-level patterns
                    continue;
                }
            }
            
            $shouldAnonymize = false;
            $updates = [];

            foreach ($properties as $propertyData) {
                $property = $propertyData['property'];
                $attribute = $propertyData['attribute'];
                $propertyName = $property->getName();

                // Check if property exists in metadata
                if (!$metadata->hasField($propertyName) && !$metadata->hasAssociation($propertyName)) {
                    continue;
                }

                // Get column name from metadata
                $columnName = $propertyName;
                if ($metadata->hasField($propertyName)) {
                    $fieldMapping = $metadata->getFieldMapping($propertyName);
                    $columnName = $fieldMapping['columnName'] ?? $propertyName;
                }

                // Check if column exists in record
                if (!isset($record[$columnName])) {
                    continue;
                }

                // Check inclusion/exclusion patterns
                if (!$this->patternMatcher->matches($record, $attribute->includePatterns, $attribute->excludePatterns)) {
                    continue;
                }

                // Generate anonymized value
                $faker = $this->getFaker($attribute->type, $attribute->service);
                $anonymizedValue = $faker->generate($attribute->options);

                // Convert value based on field type
                $anonymizedValue = $this->convertValue($anonymizedValue, $metadata, $propertyName);

                // Dispatch AnonymizePropertyEvent to allow listeners to modify or skip anonymization
                if ($this->eventDispatcher !== null) {
                    $event = new AnonymizePropertyEvent(
                        $em,
                        $metadata,
                        $property,
                        $columnName,
                        $record[$columnName] ?? null,
                        $anonymizedValue,
                        $record,
                        $dryRun
                    );
                    $this->eventDispatcher->dispatch($event);

                    // Check if listener requested to skip anonymization
                    if ($event->shouldSkipAnonymization()) {
                        continue;
                    }

                    // Use the potentially modified anonymized value
                    $anonymizedValue = $event->getAnonymizedValue();
                }

                $updates[$columnName] = $anonymizedValue;
                $shouldAnonymize = true;

                // Track property statistics
                if (!isset($propertyStats[$propertyName])) {
                    $propertyStats[$propertyName] = 0;
                }
                $propertyStats[$propertyName]++;
            }

            if ($shouldAnonymize && !$dryRun) {
                // Check if entity uses AnonymizableTrait and add anonymized flag
                if ($this->usesAnonymizableTrait($reflection)) {
                    $schemaManager = $connection->createSchemaManager();
                    if ($schemaManager->tablesExist([$tableName])) {
                        $columns = $schemaManager->listTableColumns($tableName);
                        $hasAnonymizedColumn = false;
                        foreach ($columns as $column) {
                            if ($column->getName() === 'anonymized') {
                                $hasAnonymizedColumn = true;
                                break;
                            }
                        }

                        if ($hasAnonymizedColumn) {
                            $updates['anonymized'] = true;
                        }
                    }
                }

                $this->updateRecord($connection, $tableName, $record, $updates, $metadata);
                $updated++;
            } elseif ($shouldAnonymize && $dryRun) {
                $updated++;
            }

            // Update progress
            if ($progressCallback !== null && ($index + 1) % max(1, (int) ($totalRecords / 100)) === 0 || $index + 1 === $totalRecords) {
                $progressCallback($index + 1, $totalRecords, sprintf('Processed %d/%d records (%d updated)', $index + 1, $totalRecords, $updated));
            }
        }

        // Dispatch AfterEntityAnonymizeEvent
        if ($this->eventDispatcher !== null) {
            $event = new AfterEntityAnonymizeEvent(
                $em,
                $metadata,
                $reflection,
                $processed,
                $updated,
                $propertyStats,
                $dryRun
            );
            $this->eventDispatcher->dispatch($event);
        }

        return [
            'processed' => $processed,
            'updated' => $updated,
            'propertyStats' => $propertyStats,
        ];
    }

    /**
     * Gets or creates a faker instance.
     *
     * @param string $type The faker type
     * @param string|null $serviceName The service name if type is 'service'
     * @return FakerInterface The faker instance
     */
    private function getFaker(string $type, ?string $serviceName = null): FakerInterface
    {
        $key = $type . ($serviceName ? ':' . $serviceName : '');

        if (!isset($this->fakerCache[$key])) {
            $this->fakerCache[$key] = $this->fakerFactory->create($type, $serviceName);
        }

        return $this->fakerCache[$key];
    }

    /**
     * Converts a value to the appropriate type for the database field.
     *
     * @param mixed $value The value to convert
     * @param ClassMetadata $metadata The entity metadata
     * @param string $columnName The column name
     * @return mixed The converted value
     */
    private function convertValue(mixed $value, ClassMetadata $metadata, string $columnName): mixed
    {
        // Find the field mapping
        foreach ($metadata->getFieldNames() as $fieldName) {
            $fieldMapping = $metadata->getFieldMapping($fieldName);
            if (($fieldMapping['columnName'] ?? $fieldName) === $columnName) {
                $type = $fieldMapping['type'] ?? 'string';

                return match ($type) {
                    'integer', 'int', 'smallint', 'bigint' => (int) $value,
                    'float', 'decimal' => (float) $value,
                    'boolean', 'bool' => (bool) $value,
                    default => (string) $value,
                };
            }
        }

        return $value;
    }

    /**
     * Updates a record in the database.
     *
     * @param \Doctrine\DBAL\Connection $connection The database connection
     * @param string $tableName The table name
     * @param array<string, mixed> $record The original record
     * @param array<string, mixed> $updates The updates to apply
     * @param ClassMetadata $metadata The entity metadata
     */
    private function updateRecord(
        \Doctrine\DBAL\Connection $connection,
        string $tableName,
        array $record,
        array $updates,
        ClassMetadata $metadata
    ): void {
        $identifier = $metadata->getIdentifierColumnNames();
        $where = [];

        foreach ($identifier as $idColumn) {
            $idValue = $record[$idColumn];
            // Convert to string for quote() method
            $where[] = sprintf(
                '%s = %s',
                $connection->quoteSingleIdentifier($idColumn),
                $connection->quote((string) $idValue)
            );
        }

        $set = [];
        foreach ($updates as $column => $value) {
            // Convert to string for quote() method
            $set[] = sprintf(
                '%s = %s',
                $connection->quoteSingleIdentifier($column),
                $connection->quote((string) $value)
            );
        }

        $query = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $connection->quoteSingleIdentifier($tableName),
            implode(', ', $set),
            implode(' AND ', $where)
        );

        $connection->executeStatement($query);
    }

    /**
     * Checks if a class uses the AnonymizableTrait.
     *
     * @param ReflectionClass $reflection The reflection class
     * @return bool True if the class uses AnonymizableTrait
     */
    private function usesAnonymizableTrait(ReflectionClass $reflection): bool
    {
        $traitName = 'Nowo\\AnonymizeBundle\\Trait\\AnonymizableTrait';

        foreach ($reflection->getTraitNames() as $trait) {
            if ($trait === $traitName) {
                return true;
            }
        }

        // Check parent classes
        $parent = $reflection->getParentClass();
        while ($parent !== false) {
            foreach ($parent->getTraitNames() as $trait) {
                if ($trait === $traitName) {
                    return true;
                }
            }
            $parent = $parent->getParentClass();
        }

        return false;
    }
}
