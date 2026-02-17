<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Enum\FakerType;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use ReflectionClass;
use ReflectionProperty;
use ValueError;

use function array_slice;
use function count;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;

/**
 * Service for pre-flight validation checks before anonymization.
 *
 * Validates configuration, entities, columns, patterns, and faker types
 * to prevent errors during anonymization execution.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class PreFlightCheckService
{
    /**
     * Creates a new PreFlightCheckService instance.
     *
     * @param FakerFactory $fakerFactory The faker factory for validating faker types
     */
    public function __construct(
        private FakerFactory $fakerFactory
    ) {
    }

    /**
     * Performs all pre-flight checks for the given entity manager.
     *
     * @param EntityManagerInterface $em The entity manager
     * @param array<string, array{metadata: ClassMetadata, reflection: ReflectionClass, attribute: Anonymize}> $entities The entities to check
     *
     * @return array<string, string> Array of error messages (empty if all checks pass)
     */
    public function performChecks(EntityManagerInterface $em, array $entities): array
    {
        $errors = [];

        // Check database connectivity
        $errors = array_merge($errors, $this->checkDatabaseConnectivity($em));

        // Check each entity
        foreach ($entities as $className => $entityData) {
            $metadata   = $entityData['metadata'];
            $reflection = $entityData['reflection'];
            $attribute  = $entityData['attribute'];

            // Check entity existence
            $errors = array_merge($errors, $this->checkEntityExistence($em, $className, $metadata));

            // Check properties
            $properties = $this->getAnonymizableProperties($reflection);
            foreach ($properties as $propertyName => $propertyData) {
                $property          = $propertyData['property'];
                $propertyAttribute = $propertyData['attribute'];

                // Check column existence
                $errors = array_merge($errors, $this->checkColumnExistence($em, $metadata, $propertyName, $property));

                // Validate faker type
                $errors = array_merge($errors, $this->validateFakerType($propertyAttribute));

                // Validate patterns
                $errors = array_merge($errors, $this->validatePatterns($propertyAttribute));
            }
        }

        return $errors;
    }

    /**
     * Checks database connectivity.
     *
     * @param EntityManagerInterface $em The entity manager
     *
     * @return array<string> Array of error messages
     */
    private function checkDatabaseConnectivity(EntityManagerInterface $em): array
    {
        $errors = [];

        try {
            $connection = $em->getConnection();
            // Execute a simple query to test connectivity
            // This will automatically connect if not already connected
            $connection->executeQuery('SELECT 1');
        } catch (Exception $e) {
            $errors[] = sprintf('Database connectivity check failed: %s', $e->getMessage());
        }

        return $errors;
    }

    /**
     * Checks if entity exists and is properly configured.
     *
     * @param EntityManagerInterface $em The entity manager
     * @param string $className The entity class name
     * @param ClassMetadata $metadata The entity metadata
     *
     * @return array<string> Array of error messages
     */
    private function checkEntityExistence(EntityManagerInterface $em, string $className, ClassMetadata $metadata): array
    {
        $errors = [];

        // Check if entity is mapped
        if (!$metadata->isMappedSuperclass && !$metadata->isEmbeddedClass) {
            $tableName = $metadata->getTableName();
            try {
                $connection    = $em->getConnection();
                $schemaManager = $connection->createSchemaManager();

                if (!$schemaManager->tablesExist([$tableName])) {
                    $errors[] = sprintf('Table "%s" for entity "%s" does not exist in database', $tableName, $className);
                }
            } catch (Exception $e) {
                $errors[] = sprintf('Could not check table existence for entity "%s": %s', $className, $e->getMessage());
            }
        }

        return $errors;
    }

    /**
     * Checks if column exists in the database table.
     *
     * @param EntityManagerInterface $em The entity manager
     * @param ClassMetadata $metadata The entity metadata
     * @param string $propertyName The property name
     * @param ReflectionProperty $property The reflection property
     *
     * @return array<string> Array of error messages
     */
    private function checkColumnExistence(EntityManagerInterface $em, ClassMetadata $metadata, string $propertyName, ReflectionProperty $property): array
    {
        $errors = [];

        if ($metadata->hasField($propertyName)) {
            $fieldMapping       = $metadata->getFieldMapping($propertyName);
            $expectedColumnName = $fieldMapping['columnName'] ?? $propertyName;

            try {
                $tableName     = $metadata->getTableName();
                $connection    = $em->getConnection();
                $schemaManager = $connection->createSchemaManager();

                if ($schemaManager->tablesExist([$tableName])) {
                    $columns = $schemaManager->listTableColumns($tableName);

                    // Get actual column names from database (case-sensitive)
                    $actualColumnNames    = [];
                    $columnExists         = false;
                    $caseInsensitiveMatch = null;

                    foreach ($columns as $column) {
                        $actualColumnName    = $column->getName();
                        $actualColumnNames[] = $actualColumnName;

                        // Exact match (case-sensitive)
                        if ($actualColumnName === $expectedColumnName) {
                            $columnExists = true;
                            break;
                        }

                        // Case-insensitive match (for databases that are case-insensitive)
                        if (strcasecmp($actualColumnName, $expectedColumnName) === 0) {
                            $caseInsensitiveMatch = $actualColumnName;
                        }
                    }

                    if (!$columnExists) {
                        // Build error message with suggestions
                        $errorMessage = sprintf(
                            'Column "%s" (from mapping) does not exist in table "%s" for property "%s"',
                            $expectedColumnName,
                            $tableName,
                            $propertyName,
                        );

                        // Add case-insensitive match suggestion if found
                        if ($caseInsensitiveMatch !== null) {
                            $errorMessage .= sprintf(
                                '. Found similar column "%s" (case mismatch)',
                                $caseInsensitiveMatch,
                            );
                        }

                        // Add available columns as hint (limit to first 10 to avoid huge messages)
                        $availableColumns = array_slice($actualColumnNames, 0, 10);
                        if (count($actualColumnNames) > 10) {
                            $availableColumns[] = sprintf('... and %d more', count($actualColumnNames) - 10);
                        }
                        $errorMessage .= sprintf(
                            '. Available columns: %s',
                            implode(', ', $availableColumns),
                        );

                        $errors[] = $errorMessage;
                    }
                }
            } catch (Exception $e) {
                // Column check failed, but this might be acceptable in some cases
                // We'll log it but not fail the check
            }
        }

        return $errors;
    }

    /**
     * Validates faker type and options.
     *
     * @param AnonymizeProperty $attribute The anonymize property attribute
     *
     * @return array<string> Array of error messages
     */
    private function validateFakerType(AnonymizeProperty $attribute): array
    {
        $errors = [];

        // Check if faker type is valid
        try {
            $fakerType = FakerType::from($attribute->type);
        } catch (ValueError $e) {
            $errors[] = sprintf('Invalid faker type "%s". Valid types: %s', $attribute->type, implode(', ', array_map(static fn ($case) => $case->value, FakerType::cases())));

            return $errors;
        }

        // If type is 'service', check if service name is provided
        if ($attribute->type === 'service' && empty($attribute->service)) {
            $errors[] = 'Faker type "service" requires a "service" option with the service name';
        }

        // Try to create faker to validate it works
        try {
            if ($attribute->type === 'service') {
                // Service fakers need container, skip validation for now
            } else {
                $this->fakerFactory->create($attribute->type);
            }
        } catch (Exception $e) {
            $errors[] = sprintf('Could not create faker for type "%s": %s', $attribute->type, $e->getMessage());
        }

        return $errors;
    }

    /**
     * Validates inclusion/exclusion patterns.
     *
     * @param AnonymizeProperty $attribute The anonymize property attribute
     *
     * @return array<string> Array of error messages
     */
    private function validatePatterns(AnonymizeProperty $attribute): array
    {
        $errors = [];

        // Validate include patterns (single config or list of configs)
        $errors = array_merge($errors, $this->validatePatternConfig($attribute->includePatterns, 'include'));

        // Validate exclude patterns (single config or list of configs)
        $errors = array_merge($errors, $this->validatePatternConfig($attribute->excludePatterns, 'exclude'));

        return $errors;
    }

    /**
     * Validates a pattern config: either single set (field=>pattern) or list of sets (OR between configs).
     *
     * @param array<array<int, array<string, array<string>|string>>|array<string>|string> $config
     *
     * @return array<string>
     */
    private function validatePatternConfig(array $config, string $type): array
    {
        $errors = [];
        $label  = $type === 'include' ? 'include' : 'exclude';

        if ($this->isListOfPatternSets($config)) {
            foreach ($config as $index => $set) {
                foreach ($set as $field => $pattern) {
                    $err = $this->validateOnePatternEntry($field, $pattern, $label);
                    if ($err !== null) {
                        $errors[] = sprintf('%s (config #%d)', $err, $index + 1);
                    }
                }
            }

            return $errors;
        }

        foreach ($config as $field => $pattern) {
            $err = $this->validateOnePatternEntry($field, $pattern, $label);
            if ($err !== null) {
                $errors[] = $err;
            }
        }

        return $errors;
    }

    private function isListOfPatternSets(array $config): bool
    {
        if ($config === [] || !array_is_list($config)) {
            return false;
        }
        foreach ($config as $item) {
            if (!is_array($item)) {
                return false;
            }
        }

        return true;
    }

    private function validateOnePatternEntry(mixed $field, mixed $pattern, string $label): ?string
    {
        if ($field === '' || $field === null || (is_int($field) && $field < 0)) {
            return sprintf('Invalid %s pattern: field must not be empty', $label);
        }
        if (!is_string($field) && !is_int($field)) {
            return sprintf('Invalid %s pattern: field must be string', $label);
        }
        if (is_array($pattern)) {
            if ($pattern === []) {
                return sprintf('Invalid %s pattern: pattern array must not be empty for field "%s"', $label, (string) $field);
            }
            foreach ($pattern as $p) {
                if ($p === '' && $p !== 0) {
                    return sprintf('Invalid %s pattern: pattern option must not be empty for field "%s"', $label, (string) $field);
                }
            }

            return null;
        }
        if ($pattern === '' && $pattern !== 0) {
            return sprintf('Invalid %s pattern: pattern must not be empty for field "%s"', $label, (string) $field);
        }

        return null;
    }

    /**
     * Gets anonymizable properties from reflection class.
     *
     * @param ReflectionClass $reflection The reflection class
     *
     * @return array<string, array{property: ReflectionProperty, attribute: AnonymizeProperty}>
     */
    private function getAnonymizableProperties(ReflectionClass $reflection): array
    {
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(AnonymizeProperty::class);
            if (!empty($attributes)) {
                $attribute                        = $attributes[0]->newInstance();
                $properties[$property->getName()] = [
                    'property'  => $property,
                    'attribute' => $attribute,
                ];
            }
        }

        return $properties;
    }
}
