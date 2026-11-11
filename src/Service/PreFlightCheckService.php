<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use InvalidArgumentException;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Enum\FakerType;
use Nowo\AnonymizeBundle\Faker\FakerFactoryInterface;
use Nowo\AnonymizeBundle\Faker\HashPreserveFaker;
use Nowo\AnonymizeBundle\Helper\AnonymizePropertyDiscovery;
use Nowo\AnonymizeBundle\Helper\DbalHelper;
use Nowo\AnonymizeBundle\Helper\OrmHelper;
use Nowo\AnonymizeBundle\Metadata\AnonymizeMetadataProviderInterface;
use ReflectionClass;
use ValueError;

use function array_fill_keys;
use function array_keys;
use function array_slice;
use function count;
use function explode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;
use function str_contains;
use function strtolower;

/**
 * Service for pre-flight validation checks before anonymization.
 *
 * @phpstan-type PatternValue = string|list<string>
 * @phpstan-type PatternSet = array<string, PatternValue>
 * @phpstan-type Patterns = PatternSet|list<PatternSet>
 *
 * Validates configuration, entities, columns, patterns, and faker types
 * to prevent errors during anonymization execution.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final readonly class PreFlightCheckService
{
    /**
     * Creates a new PreFlightCheckService instance.
     *
     * @param FakerFactoryInterface $fakerFactory The faker factory for validating faker types
     * @param AnonymizeMetadataProviderInterface|null $metadataProvider Since 1.5: configured fields are checked too
     */
    public function __construct(
        private FakerFactoryInterface $fakerFactory,
        private ?AnonymizeMetadataProviderInterface $metadataProvider = null,
    ) {
    }

    /**
     * Performs all pre-flight checks for the given entity manager.
     *
     * @param EntityManagerInterface $em The entity manager
     * @param array<string, array{metadata: ClassMetadata<object>, reflection: ReflectionClass<object>, attribute: Anonymize}> $entities The entities to check
     *
     * @return array<string, string> Array of error messages (empty if all checks pass)
     */
    public function performChecks(EntityManagerInterface $em, array $entities): array
    {
        $errors = [];

        // Check database connectivity
        $errors = array_merge($errors, $this->checkDatabaseConnectivity($em));

        $usesHashPreserve = false;

        // Check each entity
        foreach ($entities as $className => $entityData) {
            $metadata   = $entityData['metadata'];
            $reflection = $entityData['reflection'];
            $attribute  = $entityData['attribute'];

            // Check entity existence
            $errors = array_merge($errors, $this->checkEntityExistence($em, $className, $metadata));

            // Since 1.5: every entity-level pattern key must be something the row actually carries (LQ-N-L1)
            $errors = array_merge($errors, $this->checkPatternFields($em, $metadata, $attribute->includePatterns, $className, 'include'));
            $errors = array_merge($errors, $this->checkPatternFields($em, $metadata, $attribute->excludePatterns, $className, 'exclude'));

            // Check properties (includes Doctrine embedded paths like phoneNumber.number)
            try {
                $properties = AnonymizePropertyDiscovery::discover($reflection, $metadata, $this->metadataProvider?->getProperties($reflection->getName()) ?? []);
            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
                continue;
            }
            foreach ($properties as $propertyData) {
                $propertyName      = $propertyData['fieldName'];
                $propertyAttribute = $propertyData['attribute'];

                // Check column existence
                $errors = array_merge($errors, $this->checkColumnExistence($em, $metadata, $propertyName));

                // Validate faker type
                $errors = array_merge($errors, $this->validateFakerType($propertyAttribute));

                // Validate patterns (format, and since 1.5 that every key is a column or a joinable relation field)
                $errors = array_merge($errors, $this->validatePatterns($propertyAttribute));
                $errors = array_merge($errors, $this->checkPatternFields($em, $metadata, $propertyAttribute->includePatterns, $className . '::' . $propertyName, 'include'));
                $errors = array_merge($errors, $this->checkPatternFields($em, $metadata, $propertyAttribute->excludePatterns, $className . '::' . $propertyName, 'exclude'));

                $usesHashPreserve = $usesHashPreserve
                    || $propertyAttribute->type === FakerType::HASH_PRESERVE->value
                    || ($propertyAttribute->options['fallback_faker'] ?? null) === FakerType::HASH_PRESERVE->value;
            }
        }

        // A missing / weak hash_preserve key must stop the run before any row is written (no half-anonymized tables)
        if ($usesHashPreserve) {
            return array_merge($errors, $this->checkHashPreserveKey());
        }

        return $errors;
    }

    /**
     * Checks that the hash_preserve faker has a usable HMAC key.
     *
     * @return array<string> Array of error messages
     */
    private function checkHashPreserveKey(): array
    {
        $faker = $this->fakerFactory->create(FakerType::HASH_PRESERVE);
        if ($faker instanceof HashPreserveFaker) {
            try {
                $faker->assertUsableKey();
            } catch (InvalidArgumentException $e) {
                return [$e->getMessage()];
            }
        }

        return [];
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
     * @param ClassMetadata<object> $metadata The entity metadata
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
     * @param ClassMetadata<object> $metadata The entity metadata
     * @param string $propertyName The property name
     *
     * @return array<string> Array of error messages
     */
    private function checkColumnExistence(EntityManagerInterface $em, ClassMetadata $metadata, string $propertyName): array
    {
        $errors = [];

        if ($metadata->hasField($propertyName)) {
            $expectedColumnName = OrmHelper::getFieldColumnName($metadata, $propertyName);

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
                        $actualColumnName    = DbalHelper::getSchemaObjectName($column);
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
            } catch (Exception) {
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
            $errors[] = sprintf('Invalid faker type "%s". Valid types: %s', $attribute->type, implode(', ', array_map(static fn (FakerType $case) => $case->value, FakerType::cases())));

            return $errors;
        }

        // If type is 'service', check if service name is provided
        if ($attribute->type === 'service' && in_array($attribute->service, [null, '', '0'], true)) {
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
     * Since 1.5 (audit LQ-N-L1): a pattern key that the row does not carry matches nothing, so a typo in an include
     * key would leave every row (and its personal data) untouched while the run reports success. Keys are matched
     * against the selected row: a plain key must be a column of the entity's table (`customer_age`, `company_id`),
     * a dotted key `association.field` an owning to-one association with a single join column and a mapped field of
     * its target.
     *
     * Plain keys are compared without letter case (audit LQ-N-D-L1): PostgreSQL folds unquoted identifiers to lower
     * case (Oracle to upper case), so the row of a camelCase column `accountStatus` carries `accountstatus`, and
     * {@see PatternMatcher} falls back to the one row key that differs only in case. A key that fits two columns
     * differing only in case is ambiguous and refused. For a JOINED inheritance child only the columns of its own
     * table count (audit LQ-N-D-I1): the run reads that table alone, so inherited columns and the root discriminator
     * are not in the row.
     *
     * @param ClassMetadata<object> $metadata
     * @param Patterns $patterns
     *
     * @return list<string>
     */
    private function checkPatternFields(EntityManagerInterface $em, ClassMetadata $metadata, array $patterns, string $owner, string $type): array
    {
        // Non-string keys are reported by the format check (validatePatterns()); only named fields are looked up here.
        $keys = [];
        foreach ($this->isListOfPatternSets($patterns) ? $patterns : [$patterns] as $set) {
            foreach (array_keys($set) as $key) {
                if (is_string($key) && $key !== '' && !in_array($key, $keys, true)) {
                    $keys[] = $key;
                }
            }
        }
        if ($keys === []) {
            return [];
        }

        $joinedChild = $this->isJoinedInheritanceChild($metadata);
        $columns     = [];
        if ($joinedChild) {
            // Only the child table is read: its primary key and the fields and join columns it declares itself
            $columns = $metadata->getIdentifierColumnNames();
            foreach ($metadata->getFieldNames() as $field) {
                if (!$metadata->isInheritedField($field)) {
                    $columns[] = $metadata->getColumnName($field);
                }
            }
        } else {
            $columns = $metadata->getColumnNames();
        }
        foreach ($metadata->getAssociationNames() as $association) {
            if ($metadata->isAssociationWithSingleJoinColumn($association) && !($joinedChild && $metadata->isInheritedAssociation($association))) {
                $columns[] = $metadata->getSingleAssociationJoinColumnName($association);
            }
        }
        $discriminator = $joinedChild ? null : OrmHelper::resolveDiscriminatorColumnName($metadata->discriminatorColumn);
        if ($discriminator !== null) {
            $columns[] = $discriminator;
        }

        // Exact names, and how many columns each case-folded name stands for
        $exact  = array_fill_keys($columns, true);
        $folded = [];
        foreach (array_keys($exact) as $column) {
            $lower          = strtolower((string) $column);
            $folded[$lower] = ($folded[$lower] ?? 0) + 1;
        }

        $errors = [];
        foreach ($keys as $key) {
            if (isset($exact[$key]) || ($folded[strtolower($key)] ?? 0) === 1 || $this->isJoinablePatternField($em, $metadata, $key, $joinedChild)) {
                continue;
            }

            $errors[] = sprintf(
                'Unknown field "%s" in the %s patterns of %s: use a column of table "%s" or "association.field" of a to-one relation (a pattern on a missing field matches no row).',
                $key,
                $type,
                $owner,
                $metadata->getTableName(),
            );
        }

        return $errors;
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function isJoinablePatternField(EntityManagerInterface $em, ClassMetadata $metadata, string $key, bool $joinedChild): bool
    {
        if (!str_contains($key, '.')) {
            return false;
        }

        [$association, $field] = explode('.', $key, 2);
        if (!$metadata->hasAssociation($association) || !$metadata->isAssociationWithSingleJoinColumn($association)) {
            return false;
        }
        // The join column of an inherited association lives in a parent table, which the run does not read
        if ($joinedChild && $metadata->isInheritedAssociation($association)) {
            return false;
        }

        return $em->getClassMetadata($metadata->getAssociationTargetClass($association))->hasField($field);
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function isJoinedInheritanceChild(ClassMetadata $metadata): bool
    {
        return $metadata->isInheritanceTypeJoined() && $metadata->parentClasses !== [];
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
     * @param Patterns $config
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

    /**
     * @param Patterns $config
     *
     * @phpstan-assert-if-true list<PatternSet> $config
     */
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
                if ($p === '') {
                    return sprintf('Invalid %s pattern: pattern option must not be empty for field "%s"', $label, (string) $field);
                }
            }

            return null;
        }
        if ($pattern === '') {
            return sprintf('Invalid %s pattern: pattern must not be empty for field "%s"', $label, (string) $field);
        }

        return null;
    }
}
