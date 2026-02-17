<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Event\AfterEntityAnonymizeEvent;
use Nowo\AnonymizeBundle\Event\AnonymizePropertyEvent;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Faker\FakerInterface;
use Nowo\AnonymizeBundle\Helper\DbalHelper;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

use function count;
use function in_array;
use function is_array;
use function is_bool;
use function is_object;
use function sprintf;

use const PHP_INT_MAX;

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
     * @param ContainerInterface|null $container Optional container to resolve anonymizeService by id (required when using anonymizeService on entities)
     */
    public function __construct(
        private FakerFactory $fakerFactory,
        private PatternMatcher $patternMatcher,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private ?ContainerInterface $container = null
    ) {
    }

    /**
     * Gets all entities from the entity manager that have the Anonymize attribute.
     *
     * @param EntityManagerInterface $em The entity manager
     *
     * @return array<string, array{metadata: ClassMetadata, reflection: ReflectionClass, attribute: Anonymize}> Array of entities with their metadata
     */
    public function getAnonymizableEntities(EntityManagerInterface $em): array
    {
        $entities       = [];
        $metadataDriver = $em->getConfiguration()->getMetadataDriverImpl();

        if ($metadataDriver === null) {
            return $entities;
        }

        $classNames = $metadataDriver->getAllClassNames();

        foreach ($classNames as $className) {
            try {
                $metadata   = $em->getClassMetadata($className);
                $reflection = new ReflectionClass($className);

                $attributes = $reflection->getAttributes(Anonymize::class);
                if (empty($attributes)) {
                    continue;
                }

                $attribute            = $attributes[0]->newInstance();
                $entities[$className] = [
                    'metadata'   => $metadata,
                    'reflection' => $reflection,
                    'attribute'  => $attribute,
                ];
            } catch (Exception $e) {
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
     *
     * @return array<string, array{property: ReflectionProperty, attribute: AnonymizeProperty, weight: int}> Array of properties with their attributes and weights
     */
    public function getAnonymizableProperties(ReflectionClass $reflection): array
    {
        $properties              = [];
        $propertiesWithoutWeight = [];

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(AnonymizeProperty::class);
            if (empty($attributes)) {
                continue;
            }

            $attribute = $attributes[0]->newInstance();
            $weight    = $attribute->weight ?? PHP_INT_MAX;

            $propertyData = [
                'property'  => $property,
                'attribute' => $attribute,
                'weight'    => $weight,
            ];

            if ($weight === PHP_INT_MAX) {
                $propertiesWithoutWeight[] = $propertyData;
            } else {
                $properties[] = $propertyData;
            }
        }

        // Sort by weight
        usort($properties, static fn ($a, $b) => $a['weight'] <=> $b['weight']);

        // Sort properties without weight alphabetically
        usort($propertiesWithoutWeight, static fn ($a, $b) => $a['property']->getName() <=> $b['property']->getName());

        // Append properties without weight at the end
        return array_merge($properties, $propertiesWithoutWeight);
    }

    /**
     * Truncates (empties) tables for entities marked with truncate=true.
     *
     * If the entity is polymorphic (Doctrine Single Table or Class Table Inheritance), only rows
     * matching this entity's discriminator value are deleted. Otherwise the whole table is truncated.
     * Tables are processed in order: first by truncate_order (if defined), then alphabetically.
     *
     * @param EntityManagerInterface $em The entity manager
     * @param array<string, array{metadata: ClassMetadata, reflection: ReflectionClass, attribute: Anonymize}> $entities Array of entities with their metadata
     * @param bool $dryRun If true, only show what would be truncated without making changes
     * @param callable|null $progressCallback Optional progress callback (tableName, message)
     *
     * @return array<string, int> Array of table names and number of records deleted (or would be deleted in dry-run)
     */
    public function truncateTables(
        EntityManagerInterface $em,
        array $entities,
        bool $dryRun = false,
        ?callable $progressCallback = null
    ): array {
        $connection = $em->getConnection();
        $driverName = DbalHelper::getDriverName($connection);
        $results    = [];

        // Filter entities that have truncate=true; detect polymorphic (inheritance) to truncate only this discriminator's rows
        $tablesToTruncate = [];
        foreach ($entities as $className => $entityData) {
            $attribute = $entityData['attribute'];
            if ($attribute->truncate) {
                $metadata           = $entityData['metadata'];
                $tableName          = $metadata->getTableName();
                $discriminator      = $this->getDiscriminatorForTruncate($metadata);
                $tablesToTruncate[] = [
                    'className'           => $className,
                    'tableName'           => $tableName,
                    'order'               => $attribute->truncate_order ?? PHP_INT_MAX, // null = last
                    'discriminatorColumn' => $discriminator['column'],
                    'discriminatorValue'  => $discriminator['value'],
                ];
            }
        }

        if (empty($tablesToTruncate)) {
            return $results;
        }

        // Sort by order (lower first), then alphabetically by table name
        usort($tablesToTruncate, static function ($a, $b) {
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
                $tableName             = $tableData['tableName'];
                $className             = $tableData['className'];
                $discCol               = $tableData['discriminatorColumn'];
                $discValue             = $tableData['discriminatorValue'];
                $quotedTableName       = DbalHelper::quoteIdentifier($connection, $tableName);
                $isPolymorphicTruncate = $discCol !== null && $discValue !== null;

                if ($progressCallback !== null) {
                    $msg = $isPolymorphicTruncate
                        ? sprintf('Truncating table %s (discriminator %s = %s)...', $tableName, $discCol, $discValue)
                        : sprintf('Truncating table %s...', $tableName);
                    $progressCallback($tableName, $msg);
                }

                if ($dryRun) {
                    if ($isPolymorphicTruncate) {
                        $countQuery = sprintf(
                            'SELECT COUNT(*) as total FROM %s WHERE %s = %s',
                            $quotedTableName,
                            DbalHelper::quoteIdentifier($connection, $discCol),
                            $connection->quote($discValue),
                        );
                    } else {
                        $countQuery = sprintf('SELECT COUNT(*) as total FROM %s', $quotedTableName);
                    }
                    $count               = (int) $connection->fetchOne($countQuery);
                    $results[$tableName] = $count;
                } else {
                    if ($isPolymorphicTruncate) {
                        // Polymorphic: delete only rows for this entity's discriminator
                        $connection->executeStatement(sprintf(
                            'DELETE FROM %s WHERE %s = %s',
                            $quotedTableName,
                            DbalHelper::quoteIdentifier($connection, $discCol),
                            $connection->quote($discValue),
                        ));
                        $results[$tableName] = 0;
                    } else {
                        // Normal entity: full table truncate
                        if ($driverName === 'pdo_mysql') {
                            $connection->executeStatement(sprintf('TRUNCATE TABLE %s', $quotedTableName));
                            $results[$tableName] = 0;
                        } elseif ($driverName === 'pdo_pgsql') {
                            $connection->executeStatement(sprintf('TRUNCATE TABLE %s CASCADE', $quotedTableName));
                            $results[$tableName] = 0;
                        } elseif ($driverName === 'pdo_sqlite') {
                            $connection->executeStatement(sprintf('DELETE FROM %s', $quotedTableName));
                            $connection->executeStatement(sprintf('DELETE FROM sqlite_sequence WHERE name = %s', $connection->quote($tableName)));
                            $results[$tableName] = 0;
                        } else {
                            $connection->executeStatement(sprintf('DELETE FROM %s', $quotedTableName));
                            $results[$tableName] = 0;
                        }
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
     * Detects if the entity is polymorphic (Doctrine inheritance with discriminator) and returns column + value.
     * Used to truncate only this entity's rows when truncate=true on a child/root of STI/CTI.
     * Uses public metadata properties for compatibility with Doctrine ORM 2.x and 3.x.
     *
     * @return array{column: string|null, value: string|null}
     */
    private function getDiscriminatorForTruncate(ClassMetadata $metadata): array
    {
        $none            = 0;
        $inheritanceType = $metadata->inheritanceType ?? $none;
        if ($inheritanceType === $none) {
            return ['column' => null, 'value' => null];
        }
        $discCol   = $metadata->discriminatorColumn ?? null;
        $discValue = $metadata->discriminatorValue ?? null;
        if ($discCol === null || $discValue === null) {
            return ['column' => null, 'value' => null];
        }
        $columnName = null;
        if (is_array($discCol)) {
            $columnName = $discCol['name'] ?? $discCol['columnName'] ?? null;
        } elseif (is_object($discCol)) {
            $columnName = $discCol->name ?? ($discCol['name'] ?? null);
        }
        if ($columnName === null || $columnName === '') {
            return ['column' => null, 'value' => null];
        }

        return ['column' => $columnName, 'value' => (string) $discValue];
    }

    /**
     * Anonymizes records for a given entity.
     *
     * @param EntityManagerInterface $em The entity manager
     * @param ClassMetadata $metadata The entity metadata
     * @param ReflectionClass $reflection The entity reflection class
     * @param array<string, array{property: ReflectionProperty, attribute: AnonymizeProperty, weight: int}> $properties The properties to anonymize
     * @param int $batchSize Chunk size for reading (LIMIT per query) and for committing updates (one transaction per chunk)
     * @param bool $dryRun If true, only show what would be anonymized
     * @param AnonymizeStatistics|null $statistics Optional statistics collector
     * @param callable|null $progressCallback Optional progress callback (current, total, message)
     * @param Anonymize|null $entityAttribute Optional entity-level Anonymize attribute for filtering records
     *
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
        $processed     = 0;
        $updated       = 0;
        $propertyStats = [];
        $tableName     = $metadata->getTableName();
        $connection    = $em->getConnection();

        // Detect relationships in patterns and build query with JOINs
        $allPatterns = [];
        if ($entityAttribute !== null) {
            $allPatterns = array_merge(
                $this->getPatternFieldNames($entityAttribute->includePatterns),
                $this->getPatternFieldNames($entityAttribute->excludePatterns),
            );
        }
        foreach ($properties as $propertyData) {
            $attr        = $propertyData['attribute'];
            $allPatterns = array_merge(
                $allPatterns,
                $this->getPatternFieldNames($attr->includePatterns),
                $this->getPatternFieldNames($attr->excludePatterns),
            );
        }

        // Build query with JOINs for relationships
        $query = $this->buildQueryWithRelationships($em, $metadata, $tableName, $allPatterns);
        // For polymorphic entities, only load rows for this discriminator value
        $disc = $this->getDiscriminatorForTruncate($metadata);
        if ($disc['column'] !== null && $disc['value'] !== null) {
            $query .= sprintf(
                ' WHERE %s = %s',
                DbalHelper::quoteIdentifier($connection, $disc['column']),
                $connection->quote($disc['value']),
            );
        }

        $countQuery   = $this->buildCountQuery($connection, $metadata, $tableName, $disc['column'], $disc['value']);
        $totalRecords = (int) $connection->fetchOne($countQuery);

        if ($progressCallback !== null && $totalRecords > 0) {
            $progressCallback(0, $totalRecords, sprintf('Starting anonymization of %d records', $totalRecords));
        }

        $offset = 0;
        while (true) {
            $chunkQuery = $this->appendOrderByAndLimit($connection, $query, $metadata, 't0', $batchSize, $offset);
            $records    = $connection->fetchAllAssociative($chunkQuery);
            if (count($records) === 0) {
                break;
            }

            $connection->beginTransaction();
            try {
                $useBatch = false;
                if ($entityAttribute !== null && $entityAttribute->anonymizeService !== null && $entityAttribute->anonymizeService !== '' && $this->container !== null) {
                    try {
                        $anonymizer = $this->container->get($entityAttribute->anonymizeService);
                    } catch (Throwable $e) {
                        throw new RuntimeException(sprintf('Cannot get anonymizer service "%s": %s', $entityAttribute->anonymizeService, $e->getMessage()), 0, $e);
                    }
                    if (!$anonymizer instanceof EntityAnonymizerServiceInterface) {
                        throw new RuntimeException(sprintf('Service "%s" must implement %s.', $entityAttribute->anonymizeService, EntityAnonymizerServiceInterface::class));
                    }
                    if ($anonymizer->supportsBatch()) {
                        $useBatch   = true;
                        $allUpdates = $anonymizer->anonymizeBatch($em, $metadata, $records, $dryRun);
                        $processed += count($records);
                        foreach ($allUpdates as $index => $updates) {
                            if (empty($updates)) {
                                continue;
                            }
                            $record = $records[$index] ?? null;
                            if ($record === null) {
                                continue;
                            }
                            if ($entityAttribute !== null && !$this->patternMatcher->matches($record, $entityAttribute->includePatterns, $entityAttribute->excludePatterns)) {
                                continue;
                            }
                            if (!$dryRun) {
                                $this->updateRecord($connection, $tableName, $record, $updates, $metadata);
                            }
                            ++$updated;
                        }
                    }
                }

                if (!$useBatch) {
                    foreach ($records as $record) {
                        ++$processed;

                        // Check entity-level inclusion/exclusion patterns first
                        $isEntityExcluded = false;
                        if ($entityAttribute !== null) {
                            if (!$this->patternMatcher->matches($record, $entityAttribute->includePatterns, $entityAttribute->excludePatterns)) {
                                // Record is excluded at entity level
                                $isEntityExcluded = true;
                            }
                        }

                        // When anonymizeService is set, delegate to the service instead of property-based anonymization
                        if ($entityAttribute !== null && $entityAttribute->anonymizeService !== null && $entityAttribute->anonymizeService !== '' && $this->container !== null) {
                            if ($isEntityExcluded) {
                                continue;
                            }
                            try {
                                $anonymizer = $this->container->get($entityAttribute->anonymizeService);
                            } catch (Throwable $e) {
                                throw new RuntimeException(sprintf('Cannot get anonymizer service "%s": %s', $entityAttribute->anonymizeService, $e->getMessage()), 0, $e);
                            }
                            if (!$anonymizer instanceof EntityAnonymizerServiceInterface) {
                                throw new RuntimeException(sprintf('Service "%s" must implement %s.', $entityAttribute->anonymizeService, EntityAnonymizerServiceInterface::class));
                            }
                            $updates = $anonymizer->anonymize($em, $metadata, $record, $dryRun);
                            if (!empty($updates)) {
                                if (!$dryRun) {
                                    $this->updateRecord($connection, $tableName, $record, $updates, $metadata);
                                }
                                ++$updated;
                            }
                            continue;
                        }

                        $shouldAnonymize = false;
                        $updates         = [];

                        foreach ($properties as $propertyData) {
                            $property     = $propertyData['property'];
                            $attribute    = $propertyData['attribute'];
                            $propertyName = $property->getName();

                            // Check if property exists in metadata
                            if (!$metadata->hasField($propertyName) && !$metadata->hasAssociation($propertyName)) {
                                continue;
                            }

                            // Get column name from metadata
                            $columnName = $propertyName;
                            if ($metadata->hasField($propertyName)) {
                                $fieldMapping = $metadata->getFieldMapping($propertyName);
                                $columnName   = $fieldMapping['columnName'] ?? $propertyName;
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
                            $fakerOptions  = $attribute->options;
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
                                $mergedRecord           = array_merge($record, $updates);
                                $fakerOptions['record'] = $mergedRecord;
                            }

                            // Check if value should be null based on nullable option
                            $nullable        = $fakerOptions['nullable'] ?? false;
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
                                    $dryRun,
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
                            $shouldAnonymize      = true;

                            // Track property statistics
                            if (!isset($propertyStats[$propertyName])) {
                                $propertyStats[$propertyName] = 0;
                            }
                            ++$propertyStats[$propertyName];
                        }

                        if ($shouldAnonymize && !$dryRun) {
                            // Check if entity uses AnonymizableTrait and add anonymized flag
                            if ($this->usesAnonymizableTrait($reflection)) {
                                $schemaManager = $connection->createSchemaManager();
                                if ($schemaManager->tablesExist([$tableName])) {
                                    $columns             = $schemaManager->listTableColumns($tableName);
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
                            ++$updated;
                        } elseif ($shouldAnonymize && $dryRun) {
                            ++$updated;
                        }
                    }
                }

                $connection->commit();
            } catch (Throwable $e) {
                $connection->rollBack();
                throw $e;
            }

            if ($progressCallback !== null) {
                $progressCallback($processed, $totalRecords, sprintf('Processed %d/%d records (%d updated)', $processed, $totalRecords, $updated));
            }
            $offset += $batchSize;
            if (count($records) < $batchSize) {
                break;
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
                $dryRun,
            );
            $this->eventDispatcher->dispatch($event);
        }

        return [
            'processed'     => $processed,
            'updated'       => $updated,
            'propertyStats' => $propertyStats,
        ];
    }

    /**
     * Gets or creates a faker instance.
     *
     * @param string $type The faker type
     * @param string|null $serviceName The service name if type is 'service'
     *
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
     *
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
        $where      = [];

        foreach ($identifier as $idColumn) {
            $idValue = $record[$idColumn];
            // Convert to string for quote() method
            $where[] = sprintf(
                '%s = %s',
                DbalHelper::quoteIdentifier($connection, $idColumn),
                $connection->quote((string) $idValue),
            );
        }

        $set        = [];
        $isPostgres = str_contains(DbalHelper::getDriverName($connection), 'pgsql');
        foreach ($updates as $column => $value) {
            // Handle boolean values: PostgreSQL requires TRUE/FALSE, others accept 1/0
            if (is_bool($value)) {
                $quotedValue = $value ? ($isPostgres ? 'TRUE' : '1') : ($isPostgres ? 'FALSE' : '0');
            } elseif ($value === null) {
                $quotedValue = 'NULL';
            } else {
                // Convert to string for quote() method
                $quotedValue = $connection->quote((string) $value);
            }

            $set[] = sprintf(
                '%s = %s',
                DbalHelper::quoteIdentifier($connection, $column),
                $quotedValue,
            );
        }

        $query = sprintf(
            'UPDATE %s SET %s WHERE %s',
            DbalHelper::quoteIdentifier($connection, $tableName),
            implode(', ', $set),
            implode(' AND ', $where),
        );

        $connection->executeStatement($query);
    }

    /**
     * Checks if a class uses the AnonymizableTrait.
     *
     * @param ReflectionClass $reflection The reflection class
     *
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
     * @param array<array<int, array<string, array<string>|string>>|array<string>|string> $patterns
     *
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
     *
     * @return string The SQL query with JOINs
     */
    private function buildQueryWithRelationships(
        EntityManagerInterface $em,
        ClassMetadata $metadata,
        string $tableName,
        array $patternFields
    ): string {
        $connection     = $em->getConnection();
        $mainTableAlias = 't0';
        $joins          = [];
        $selectFields   = [DbalHelper::quoteIdentifier($connection, $mainTableAlias) . '.*'];
        $joinCounter    = 1;

        // Detect relationship patterns (fields with dots, e.g., 'type.name')
        foreach ($patternFields as $patternField) {
            if (!str_contains($patternField, '.')) {
                continue; // Not a relationship pattern
            }

            $parts           = explode('.', $patternField, 2);
            $associationName = $parts[0];
            $relatedField    = $parts[1];

            // Check if association exists
            if (!$metadata->hasAssociation($associationName)) {
                continue; // Skip if association doesn't exist
            }

            $associationMapping = $metadata->getAssociationMapping($associationName);
            $targetEntity       = $associationMapping['targetEntity'];
            $targetMetadata     = $em->getClassMetadata($targetEntity);
            $targetTable        = $targetMetadata->getTableName();
            $alias              = 't' . $joinCounter;

            // Get join columns
            $joinColumns = $associationMapping['joinColumns'] ?? [];
            if (empty($joinColumns)) {
                // ManyToOne or OneToOne - use source column
                $sourceColumn = $metadata->getSingleAssociationJoinColumnName($associationName);
                // Get target ID column name
                $targetIdField   = $targetMetadata->getSingleIdentifierFieldName();
                $targetIdMapping = $targetMetadata->getFieldMapping($targetIdField);
                $targetColumn    = $targetIdMapping['columnName'] ?? 'id';
            } else {
                $joinColumn   = $joinColumns[0];
                $sourceColumn = $joinColumn['name'] ?? $associationName . '_id';
                $targetColumn = $joinColumn['referencedColumnName'] ?? 'id';
            }

            // Check if join already exists
            $joinKey = $associationName;
            if (!isset($joins[$joinKey])) {
                $joins[$joinKey] = [
                    'table'        => $targetTable,
                    'alias'        => $alias,
                    'sourceColumn' => $sourceColumn,
                    'targetColumn' => $targetColumn,
                    'counter'      => $joinCounter,
                ];

                // Add related field to SELECT if it exists in target metadata
                if ($targetMetadata->hasField($relatedField)) {
                    $relatedFieldMapping = $targetMetadata->getFieldMapping($relatedField);
                    $relatedColumnName   = $relatedFieldMapping['columnName'] ?? $relatedField;

                    // Validate that we have valid identifiers before adding to SELECT
                    if (!empty($relatedColumnName) && !empty($alias)) {
                        $quotedAlias   = DbalHelper::quoteIdentifier($connection, $alias);
                        $quotedColumn  = DbalHelper::quoteIdentifier($connection, $relatedColumnName);
                        $quotedPattern = DbalHelper::quoteIdentifier($connection, $patternField);

                        // Only add if all quoted identifiers are non-empty
                        if (!empty($quotedAlias) && !empty($quotedColumn) && !empty($quotedPattern)) {
                            $selectFields[] = sprintf(
                                '%s.%s AS %s',
                                $quotedAlias,
                                $quotedColumn,
                                $quotedPattern,
                            );
                        }
                    }
                }

                ++$joinCounter;
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
            DbalHelper::quoteIdentifier($connection, $mainTableAlias),
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
                DbalHelper::quoteIdentifier($connection, $join['targetColumn']),
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

    /**
     * Builds a COUNT(*) query for the main table with optional discriminator filter.
     * Used to get total record count for progress without loading all rows.
     *
     * @param \Doctrine\DBAL\Connection $connection The database connection
     * @param ClassMetadata $metadata The entity metadata
     * @param string $tableName The main table name
     * @param string|null $discColumn Discriminator column name (null if not polymorphic)
     * @param mixed $discValue Discriminator value
     *
     * @return string The SQL COUNT query
     */
    private function buildCountQuery(
        \Doctrine\DBAL\Connection $connection,
        ClassMetadata $metadata,
        string $tableName,
        ?string $discColumn,
        mixed $discValue
    ): string {
        $mainTableAlias = 't0';
        $from           = sprintf(
            '%s AS %s',
            DbalHelper::quoteIdentifier($connection, $tableName),
            DbalHelper::quoteIdentifier($connection, $mainTableAlias),
        );
        $query = 'SELECT COUNT(*) FROM ' . $from;
        if ($discColumn !== null && $discValue !== null) {
            $query .= sprintf(
                ' WHERE %s = %s',
                DbalHelper::quoteIdentifier($connection, $discColumn),
                $connection->quote((string) $discValue),
            );
        }

        return $query;
    }

    /**
     * Appends ORDER BY primary key and LIMIT/OFFSET to a query for stable chunked reads.
     *
     * @param \Doctrine\DBAL\Connection $connection The database connection
     * @param string $query The base SELECT query (with WHERE if any)
     * @param ClassMetadata $metadata The entity metadata
     * @param string $mainTableAlias The main table alias (e.g. t0)
     * @param int $limit Maximum number of rows
     * @param int $offset Offset for pagination
     *
     * @return string The query with ORDER BY, LIMIT and OFFSET
     */
    private function appendOrderByAndLimit(
        \Doctrine\DBAL\Connection $connection,
        string $query,
        ClassMetadata $metadata,
        string $mainTableAlias,
        int $limit,
        int $offset
    ): string {
        $idColumns  = $metadata->getIdentifierColumnNames();
        $orderParts = [];
        foreach ($idColumns as $col) {
            $orderParts[] = DbalHelper::quoteIdentifier($connection, $mainTableAlias) . '.'
                . DbalHelper::quoteIdentifier($connection, $col);
        }
        $suffix = sprintf(' LIMIT %d OFFSET %d', $limit, $offset);
        if (empty($orderParts)) {
            return $query . $suffix;
        }

        return $query . ' ORDER BY ' . implode(', ', $orderParts) . $suffix;
    }
}
