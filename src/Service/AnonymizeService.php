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
use Nowo\AnonymizeBundle\Helper\DbalHelper;
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
     * Truncates (empties) tables for entities marked with truncate=true.
     *
     * This method should be called BEFORE anonymization to empty tables that need to be cleared.
     * Tables are processed in order: first by truncate_order (if defined), then alphabetically.
     *
     * @param EntityManagerInterface $em The entity manager
     * @param array<string, array{metadata: ClassMetadata, reflection: ReflectionClass, attribute: Anonymize}> $entities Array of entities with their metadata
     * @param bool $dryRun If true, only show what would be truncated without making changes
     * @param callable|null $progressCallback Optional progress callback (tableName, message)
     * @return array<string, int> Array of table names and number of records deleted (or would be deleted in dry-run)
     */
    public function truncateTables(
        EntityManagerInterface $em,
        array $entities,
        bool $dryRun = false,
        ?callable $progressCallback = null
    ): array {
        $connection = $em->getConnection();
        $driverName = \Nowo\AnonymizeBundle\Helper\DbalHelper::getDriverName($connection);
        $results = [];

        // Filter entities that have truncate=true
        $tablesToTruncate = [];
        foreach ($entities as $className => $entityData) {
            $attribute = $entityData['attribute'];
            if ($attribute->truncate) {
                $metadata = $entityData['metadata'];
                $tableName = $metadata->getTableName();
                $tablesToTruncate[] = [
                    'className' => $className,
                    'tableName' => $tableName,
                    'order' => $attribute->truncate_order ?? PHP_INT_MAX, // null = last
                ];
            }
        }

        if (empty($tablesToTruncate)) {
            return $results;
        }

        // Sort by order (lower first), then alphabetically by table name
        usort($tablesToTruncate, function ($a, $b) {
            if ($a['order'] !== $b['order']) {
                return $a['order'] <=> $b['order'];
            }
            return strcmp($a['tableName'], $b['tableName']);
        });

        // Handle foreign keys based on database type
        $foreignKeysDisabled = false;
        if ($driverName === 'pdo_mysql') {
            // MySQL: Disable foreign key checks temporarily
            if (!$dryRun) {
                $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
                $foreignKeysDisabled = true;
            }
        } elseif ($driverName === 'pdo_sqlite') {
            // SQLite: Disable foreign key checks temporarily
            if (!$dryRun) {
                $connection->executeStatement('PRAGMA foreign_keys = OFF');
                $foreignKeysDisabled = true;
            }
        }
        // PostgreSQL: TRUNCATE CASCADE handles foreign keys automatically

        try {
            foreach ($tablesToTruncate as $tableData) {
                $tableName = $tableData['tableName'];
                $className = $tableData['className'];
                $quotedTableName = \Nowo\AnonymizeBundle\Helper\DbalHelper::quoteIdentifier($connection, $tableName);

                if ($progressCallback !== null) {
                    $progressCallback($tableName, sprintf('Truncating table %s...', $tableName));
                }

                if ($dryRun) {
                    // In dry-run, count records that would be deleted
                    $countQuery = sprintf('SELECT COUNT(*) as total FROM %s', $quotedTableName);
                    $count = (int) $connection->fetchOne($countQuery);
                    $results[$tableName] = $count;
                } else {
                    // Execute TRUNCATE or DELETE based on database type
                    if ($driverName === 'pdo_mysql') {
                        // MySQL: Use TRUNCATE TABLE
                        $connection->executeStatement(sprintf('TRUNCATE TABLE %s', $quotedTableName));
                        $results[$tableName] = 0; // TRUNCATE doesn't return count
                    } elseif ($driverName === 'pdo_pgsql') {
                        // PostgreSQL: Use TRUNCATE TABLE CASCADE to handle foreign keys
                        $connection->executeStatement(sprintf('TRUNCATE TABLE %s CASCADE', $quotedTableName));
                        $results[$tableName] = 0; // TRUNCATE doesn't return count
                    } elseif ($driverName === 'pdo_sqlite') {
                        // SQLite: Use DELETE FROM (TRUNCATE not supported)
                        $connection->executeStatement(sprintf('DELETE FROM %s', $quotedTableName));
                        // Reset auto-increment
                        $connection->executeStatement(sprintf('DELETE FROM sqlite_sequence WHERE name = %s', $connection->quote($tableName)));
                        $results[$tableName] = 0; // DELETE doesn't return count easily
                    } else {
                        // Fallback: Use DELETE FROM
                        $connection->executeStatement(sprintf('DELETE FROM %s', $quotedTableName));
                        $results[$tableName] = 0;
                    }
                }
            }
        } finally {
            // Re-enable foreign key checks
            if ($foreignKeysDisabled) {
                if ($driverName === 'pdo_mysql') {
                    $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
                } elseif ($driverName === 'pdo_sqlite') {
                    $connection->executeStatement('PRAGMA foreign_keys = ON');
                }
            }
        }

        return $results;
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

        // Detect relationships in patterns and build query with JOINs
        $allPatterns = [];
        if ($entityAttribute !== null) {
            $allPatterns = array_merge(
                $this->getPatternFieldNames($entityAttribute->includePatterns),
                $this->getPatternFieldNames($entityAttribute->excludePatterns)
            );
        }
        foreach ($properties as $propertyData) {
            $attr = $propertyData['attribute'];
            $allPatterns = array_merge(
                $allPatterns,
                $this->getPatternFieldNames($attr->includePatterns),
                $this->getPatternFieldNames($attr->excludePatterns)
            );
        }

        // Build query with JOINs for relationships
        $query = $this->buildQueryWithRelationships($em, $metadata, $tableName, $allPatterns);
        $records = $connection->fetchAllAssociative($query);
        $totalRecords = count($records);

        if ($progressCallback !== null && $totalRecords > 0) {
            $progressCallback(0, $totalRecords, sprintf('Starting anonymization of %d records', $totalRecords));
        }

        foreach ($records as $index => $record) {
            $processed++;

            // Check entity-level inclusion/exclusion patterns first
            $isEntityExcluded = false;
            if ($entityAttribute !== null) {
                if (!$this->patternMatcher->matches($record, $entityAttribute->includePatterns, $entityAttribute->excludePatterns)) {
                    // Record is excluded at entity level
                    $isEntityExcluded = true;
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

                // Check if this field should bypass entity exclusion
                $bypassEntityExclusion = $attribute->options['bypass_entity_exclusion'] ?? false;

                // If entity is excluded and this field doesn't bypass exclusion, skip it
                if ($isEntityExcluded && !$bypassEntityExclusion) {
                    continue;
                }

                // Check inclusion/exclusion patterns (only if not bypassing entity exclusion or entity is not excluded)
                if (!$bypassEntityExclusion || !$isEntityExcluded) {
                    if (!$this->patternMatcher->matches($record, $attribute->includePatterns, $attribute->excludePatterns)) {
                        continue;
                    }
                }

                // Generate anonymized value
                $faker = $this->getFaker($attribute->type, $attribute->service);

                // Always pass the original value to all fakers for consistency and versatility
                // This allows fakers to use the original value if needed (e.g., hash_preserve, masking)
                // or ignore it if not needed (most fakers)
                $fakerOptions = $attribute->options;
                $originalValue = $record[$columnName] ?? null;

                // Check if we should preserve null values (skip anonymization if original is null)
                $preserveNull = $fakerOptions['preserve_null'] ?? false;
                if ($preserveNull && $originalValue === null) {
                    // Skip anonymization for this property if original value is null
                    continue;
                }

                // Set original_value (standard key for all fakers)
                if (!isset($fakerOptions['original_value'])) {
                    $fakerOptions['original_value'] = $originalValue;
                }

                // For backward compatibility with hash_preserve and masking (they use 'value' key)
                if (in_array($attribute->type, ['hash_preserve', 'masking']) && !isset($fakerOptions['value'])) {
                    $fakerOptions['value'] = $originalValue;
                }

                // For name_fallback faker, pass the full record to check related fields
                if ($attribute->type === 'name_fallback' && !isset($fakerOptions['record'])) {
                    $fakerOptions['record'] = $record;
                }

                // For pattern_based and copy fakers, pass the record with already anonymized values merged
                // This allows them to use anonymized values from other fields (e.g., email)
                if (in_array($attribute->type, ['pattern_based', 'copy']) && !isset($fakerOptions['record'])) {
                    // Merge original record with already anonymized values from $updates
                    // This ensures these fakers can access the anonymized value of source_field
                    $mergedRecord = array_merge($record, $updates);
                    $fakerOptions['record'] = $mergedRecord;
                }

                // Check if value should be null based on nullable option
                $nullable = $fakerOptions['nullable'] ?? false;
                $nullProbability = (int) ($fakerOptions['null_probability'] ?? 0);

                // If nullable is enabled and random chance determines it should be null
                if ($nullable && $nullProbability > 0) {
                    // Generate random number 0-99 and check if it's below the probability threshold
                    // null_probability of 30 means 30% chance of being null (0-29 out of 0-99)
                    // null_probability of 100 means 100% chance of being null (0-99 out of 0-99)
                    $random = mt_rand(0, 99);
                    if ($random < $nullProbability) {
                        $anonymizedValue = null;
                    } else {
                        $anonymizedValue = $faker->generate($fakerOptions);
                    }
                } else {
                    $anonymizedValue = $faker->generate($fakerOptions);
                }

                // Convert value based on field type (but preserve null values)
                if ($anonymizedValue !== null) {
                    $anonymizedValue = $this->convertValue($anonymizedValue, $metadata, $propertyName);
                }

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
            if ($progressCallback !== null && (($index + 1) % max(1, (int) ($totalRecords / 100)) === 0 || $index + 1 === $totalRecords)) {
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
                DbalHelper::quoteIdentifier($connection, $idColumn),
                $connection->quote((string) $idValue)
            );
        }

        $set = [];
        foreach ($updates as $column => $value) {
            // Handle boolean values specially - convert to 0/1 for database
            if (is_bool($value)) {
                $quotedValue = $value ? '1' : '0';
            } elseif ($value === null) {
                $quotedValue = 'NULL';
            } else {
                // Convert to string for quote() method
                $quotedValue = $connection->quote((string) $value);
            }

            $set[] = sprintf(
                '%s = %s',
                DbalHelper::quoteIdentifier($connection, $column),
                $quotedValue
            );
        }

        $query = sprintf(
            'UPDATE %s SET %s WHERE %s',
            DbalHelper::quoteIdentifier($connection, $tableName),
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

    /**
     * Extracts all field names from a patterns array (single config or list of configs).
     *
     * @param array<string|array<string>|array<int, array<string, string|array<string>>>> $patterns
     * @return array<int, string>
     */
    private function getPatternFieldNames(array $patterns): array
    {
        if ($patterns === []) {
            return [];
        }
        if ($this->isListOfPatternSets($patterns)) {
            $names = [];
            foreach ($patterns as $set) {
                $names = array_merge($names, array_keys($set));
            }
            return array_values(array_unique($names));
        }
        return array_keys($patterns);
    }

    private function isListOfPatternSets(array $patterns): bool
    {
        if ($patterns === [] || !array_is_list($patterns)) {
            return false;
        }
        foreach ($patterns as $item) {
            if (!is_array($item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Builds a SQL query with JOINs for relationships referenced in patterns.
     *
     * @param EntityManagerInterface $em The entity manager
     * @param ClassMetadata $metadata The entity metadata
     * @param string $tableName The main table name
     * @param array<string> $patternFields Array of pattern field names (e.g., ['id', 'type.name', 'status'])
     * @return string The SQL query with JOINs
     */
    private function buildQueryWithRelationships(
        EntityManagerInterface $em,
        ClassMetadata $metadata,
        string $tableName,
        array $patternFields
    ): string {
        $connection = $em->getConnection();
        $mainTableAlias = 't0';
        $joins = [];
        $selectFields = [DbalHelper::quoteIdentifier($connection, $mainTableAlias) . '.*'];
        $joinCounter = 1;

        // Detect relationship patterns (fields with dots, e.g., 'type.name')
        foreach ($patternFields as $patternField) {
            if (!str_contains($patternField, '.')) {
                continue; // Not a relationship pattern
            }

            $parts = explode('.', $patternField, 2);
            $associationName = $parts[0];
            $relatedField = $parts[1];

            // Check if association exists
            if (!$metadata->hasAssociation($associationName)) {
                continue; // Skip if association doesn't exist
            }

            $associationMapping = $metadata->getAssociationMapping($associationName);
            $targetEntity = $associationMapping['targetEntity'];
            $targetMetadata = $em->getClassMetadata($targetEntity);
            $targetTable = $targetMetadata->getTableName();
            $alias = 't' . $joinCounter;

            // Get join columns
            $joinColumns = $associationMapping['joinColumns'] ?? [];
            if (empty($joinColumns)) {
                // ManyToOne or OneToOne - use source column
                $sourceColumn = $metadata->getSingleAssociationJoinColumnName($associationName);
                // Get target ID column name
                $targetIdField = $targetMetadata->getSingleIdentifierFieldName();
                $targetIdMapping = $targetMetadata->getFieldMapping($targetIdField);
                $targetColumn = $targetIdMapping['columnName'] ?? 'id';
            } else {
                $joinColumn = $joinColumns[0];
                $sourceColumn = $joinColumn['name'] ?? $associationName . '_id';
                $targetColumn = $joinColumn['referencedColumnName'] ?? 'id';
            }

            // Check if join already exists
            $joinKey = $associationName;
            if (!isset($joins[$joinKey])) {
                $joins[$joinKey] = [
                    'table' => $targetTable,
                    'alias' => $alias,
                    'sourceColumn' => $sourceColumn,
                    'targetColumn' => $targetColumn,
                    'counter' => $joinCounter,
                ];

                // Add related field to SELECT if it exists in target metadata
                if ($targetMetadata->hasField($relatedField)) {
                    $relatedFieldMapping = $targetMetadata->getFieldMapping($relatedField);
                    $relatedColumnName = $relatedFieldMapping['columnName'] ?? $relatedField;

                    // Validate that we have valid identifiers before adding to SELECT
                    if (!empty($relatedColumnName) && !empty($alias)) {
                        $quotedAlias = DbalHelper::quoteIdentifier($connection, $alias);
                        $quotedColumn = DbalHelper::quoteIdentifier($connection, $relatedColumnName);
                        $quotedPattern = DbalHelper::quoteIdentifier($connection, $patternField);

                        // Only add if all quoted identifiers are non-empty
                        if (!empty($quotedAlias) && !empty($quotedColumn) && !empty($quotedPattern)) {
                            $selectFields[] = sprintf(
                                '%s.%s AS %s',
                                $quotedAlias,
                                $quotedColumn,
                                $quotedPattern
                            );
                        }
                    }
                }

                $joinCounter++;
            }
        }

        // Build SELECT clause
        // Always start with main table fields - ensure it's properly quoted
        $mainTableSelect = DbalHelper::quoteIdentifier($connection, $mainTableAlias) . '.*';

        // Build list of valid select fields
        // Start fresh and always include main table first
        $validSelectFields = [$mainTableSelect];

        // Add related fields that are not empty and different from main table select
        foreach ($selectFields as $field) {
            $trimmed = trim($field);
            if (!empty($trimmed) && $trimmed !== $mainTableSelect) {
                $validSelectFields[] = $trimmed;
            }
        }

        $selectClause = implode(', ', $validSelectFields);

        // Build FROM clause
        $fromClause = sprintf(
            '%s AS %s',
            DbalHelper::quoteIdentifier($connection, $tableName),
            DbalHelper::quoteIdentifier($connection, $mainTableAlias)
        );

        // Build JOIN clauses
        $joinClauses = [];
        foreach ($joins as $join) {
            $joinClauses[] = sprintf(
                'LEFT JOIN %s AS %s ON %s.%s = %s.%s',
                DbalHelper::quoteIdentifier($connection, $join['table']),
                DbalHelper::quoteIdentifier($connection, $join['alias']),
                DbalHelper::quoteIdentifier($connection, $mainTableAlias),
                DbalHelper::quoteIdentifier($connection, $join['sourceColumn']),
                DbalHelper::quoteIdentifier($connection, $join['alias']),
                DbalHelper::quoteIdentifier($connection, $join['targetColumn'])
            );
        }

        // Build final query
        // Validate SELECT clause before building query
        if (empty(trim($selectClause)) || str_starts_with(trim($selectClause), '.')) {
            // If SELECT clause is malformed, use only main table fields
            $selectClause = $mainTableSelect;
        }

        $query = sprintf('SELECT %s FROM %s', $selectClause, $fromClause);
        if (!empty($joinClauses)) {
            $query .= ' ' . implode(' ', $joinClauses);
        }

        return $query;
    }
}
