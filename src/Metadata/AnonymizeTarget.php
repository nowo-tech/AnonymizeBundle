<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Metadata;

use InvalidArgumentException;
use Nowo\AnonymizeBundle\Service\RowAnonymizerInterface;

use function array_key_exists;
use function sprintf;

/**
 * ORM-free description of the class and table one anonymization step works on (since 1.6, REQ-ARCH-001 rule 3).
 *
 * Carried by the row SPI {@see RowAnonymizerInterface} and by the per-class and
 * per-property events, so a host listener or anonymizer needs no Doctrine type. The bundle builds it from the
 * Doctrine metadata of the class being anonymized; hosts only read it (tests may build it directly).
 *
 * Holds names only, never connection parameters or row data, so dumping it reveals nothing secret.
 *
 * @since 1.6.0
 */
final readonly class AnonymizeTarget
{
    /**
     * @param class-string|string $entityClass The anonymized class (the concrete class, e.g. a child in single-table inheritance)
     * @param string $tableName The table the rows are read from and written to
     * @param list<string> $identifierColumns The primary-key column names (the UPDATE's WHERE columns)
     * @param array<string, string> $columns Mapped field name => column name (embedded fields use `embedProperty.field`)
     * @param string|null $discriminatorColumn The discriminator column when the class is part of an inheritance hierarchy
     * @param string|null $discriminatorValue The discriminator value of this class (only its rows are read)
     */
    public function __construct(
        private string $entityClass,
        private string $tableName,
        private array $identifierColumns = [],
        private array $columns = [],
        private ?string $discriminatorColumn = null,
        private ?string $discriminatorValue = null,
    ) {
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @return list<string>
     */
    public function getIdentifierColumns(): array
    {
        return $this->identifierColumns;
    }

    /**
     * @return array<string, string> Mapped field name => column name
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function hasField(string $fieldName): bool
    {
        return array_key_exists($fieldName, $this->columns);
    }

    /**
     * The column of a mapped field.
     *
     * @throws InvalidArgumentException When the field is not mapped
     */
    public function getColumnName(string $fieldName): string
    {
        if (!array_key_exists($fieldName, $this->columns)) {
            throw new InvalidArgumentException(sprintf('Field "%s" is not mapped on %s (table "%s").', $fieldName, $this->entityClass, $this->tableName));
        }

        return $this->columns[$fieldName];
    }

    public function getDiscriminatorColumn(): ?string
    {
        return $this->discriminatorColumn;
    }

    public function getDiscriminatorValue(): ?string
    {
        return $this->discriminatorValue;
    }
}
