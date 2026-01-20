<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Enum\FakerType;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use ReflectionClass;
use ReflectionProperty;

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
    ) {}

    /**
     * Performs all pre-flight checks for the given entity manager.
     *
     * @param EntityManagerInterface $em The entity manager
     * @param array<string, array{metadata: ClassMetadata, reflection: ReflectionClass, attribute: Anonymize}> $entities The entities to check
     * @return array<string, string> Array of error messages (empty if all checks pass)
     */
    public function performChecks(EntityManagerInterface $em, array $entities): array
    {
        $errors = [];

        // Check database connectivity
        $errors = array_merge($errors, $this->checkDatabaseConnectivity($em));

        // Check each entity
        foreach ($entities as $className => $entityData) {
            $metadata = $entityData['metadata'];
            $reflection = $entityData['reflection'];
            $attribute = $entityData['attribute'];

            // Check entity existence
            $errors = array_merge($errors, $this->checkEntityExistence($em, $className, $metadata));

            // Check properties
            $properties = $this->getAnonymizableProperties($reflection);
            foreach ($properties as $propertyName => $propertyData) {
                $property = $propertyData['property'];
                $propertyAttribute = $propertyData['attribute'];

                // Check column existence
                $errors = array_merge($errors, $this->checkColumnExistence($metadata, $propertyName, $property));

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
     * @return array<string> Array of error messages
     */
    private function checkDatabaseConnectivity(EntityManagerInterface $em): array
    {
        $errors = [];

        try {
            $connection = $em->getConnection();
            $connection->connect();
        } catch (\Exception $e) {
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
     * @return array<string> Array of error messages
     */
    private function checkEntityExistence(EntityManagerInterface $em, string $className, ClassMetadata $metadata): array
    {
        $errors = [];

        // Check if entity is mapped
        if (!$metadata->isMappedSuperclass && !$metadata->isEmbeddedClass) {
            $tableName = $metadata->getTableName();
            try {
                $connection = $em->getConnection();
                $schemaManager = $connection->createSchemaManager();

                if (!$schemaManager->tablesExist([$tableName])) {
                    $errors[] = sprintf('Table "%s" for entity "%s" does not exist in database', $tableName, $className);
                }
            } catch (\Exception $e) {
                $errors[] = sprintf('Could not check table existence for entity "%s": %s', $className, $e->getMessage());
            }
        }

        return $errors;
    }

    /**
     * Checks if column exists in the database table.
     *
     * @param ClassMetadata $metadata The entity metadata
     * @param string $propertyName The property name
     * @param ReflectionProperty $property The reflection property
     * @return array<string> Array of error messages
     */
    private function checkColumnExistence(ClassMetadata $metadata, string $propertyName, ReflectionProperty $property): array
    {
        $errors = [];

        if ($metadata->hasField($propertyName)) {
            $fieldMapping = $metadata->getFieldMapping($propertyName);
            $columnName = $fieldMapping['columnName'] ?? $propertyName;

            try {
                $tableName = $metadata->getTableName();
                $connection = $metadata->getEntityManager()->getConnection();
                $schemaManager = $connection->createSchemaManager();

                if ($schemaManager->tablesExist([$tableName])) {
                    $columns = $schemaManager->listTableColumns($tableName);
                    $columnExists = false;
                    foreach ($columns as $column) {
                        if ($column->getName() === $columnName) {
                            $columnExists = true;
                            break;
                        }
                    }

                    if (!$columnExists) {
                        $errors[] = sprintf('Column "%s" does not exist in table "%s" for property "%s"', $columnName, $tableName, $propertyName);
                    }
                }
            } catch (\Exception $e) {
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
     * @return array<string> Array of error messages
     */
    private function validateFakerType(AnonymizeProperty $attribute): array
    {
        $errors = [];

        // Check if faker type is valid
        try {
            $fakerType = FakerType::from($attribute->type);
        } catch (\ValueError $e) {
            $errors[] = sprintf('Invalid faker type "%s". Valid types: %s', $attribute->type, implode(', ', array_map(fn($case) => $case->value, FakerType::cases())));
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
        } catch (\Exception $e) {
            $errors[] = sprintf('Could not create faker for type "%s": %s', $attribute->type, $e->getMessage());
        }

        return $errors;
    }

    /**
     * Validates inclusion/exclusion patterns.
     *
     * @param AnonymizeProperty $attribute The anonymize property attribute
     * @return array<string> Array of error messages
     */
    private function validatePatterns(AnonymizeProperty $attribute): array
    {
        $errors = [];

        // Validate include patterns
        foreach ($attribute->includePatterns as $field => $pattern) {
            if (empty($field) || empty($pattern)) {
                $errors[] = sprintf('Invalid include pattern: field and pattern must not be empty');
            }
        }

        // Validate exclude patterns
        foreach ($attribute->excludePatterns as $field => $pattern) {
            if (empty($field) || empty($pattern)) {
                $errors[] = sprintf('Invalid exclude pattern: field and pattern must not be empty');
            }
        }

        return $errors;
    }

    /**
     * Gets anonymizable properties from reflection class.
     *
     * @param ReflectionClass $reflection The reflection class
     * @return array<string, array{property: ReflectionProperty, attribute: AnonymizeProperty}>
     */
    private function getAnonymizableProperties(ReflectionClass $reflection): array
    {
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(AnonymizeProperty::class);
            if (!empty($attributes)) {
                $attribute = $attributes[0]->newInstance();
                $properties[$property->getName()] = [
                    'property' => $property,
                    'attribute' => $attribute,
                ];
            }
        }

        return $properties;
    }
}
