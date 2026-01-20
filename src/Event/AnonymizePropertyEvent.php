<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionProperty;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before anonymizing a specific property.
 *
 * This event is dispatched for each property that is about to be anonymized,
 * allowing listeners to modify the anonymized value or skip anonymization.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AnonymizePropertyEvent extends Event
{
    /**
     * @var mixed The anonymized value (can be modified by listeners)
     */
    private mixed $anonymizedValue;

    /**
     * @var bool Whether to skip anonymization of this property
     */
    private bool $skipAnonymization = false;

    /**
     * Creates a new AnonymizePropertyEvent instance.
     *
     * @param EntityManagerInterface $entityManager The entity manager
     * @param ClassMetadata $metadata The entity metadata
     * @param ReflectionProperty $property The property reflection
     * @param string $columnName The database column name
     * @param mixed $originalValue The original value before anonymization
     * @param mixed $anonymizedValue The anonymized value
     * @param array<string, mixed> $record The full database record
     * @param bool $dryRun Whether this is a dry run
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClassMetadata $metadata,
        private readonly ReflectionProperty $property,
        private readonly string $columnName,
        private readonly mixed $originalValue,
        mixed $anonymizedValue,
        private readonly array $record,
        private readonly bool $dryRun = false
    ) {
        $this->anonymizedValue = $anonymizedValue;
    }

    /**
     * Gets the entity manager.
     *
     * @return EntityManagerInterface The entity manager
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * Gets the entity metadata.
     *
     * @return ClassMetadata The entity metadata
     */
    public function getMetadata(): ClassMetadata
    {
        return $this->metadata;
    }

    /**
     * Gets the property reflection.
     *
     * @return ReflectionProperty The property reflection
     */
    public function getProperty(): ReflectionProperty
    {
        return $this->property;
    }

    /**
     * Gets the property name.
     *
     * @return string The property name
     */
    public function getPropertyName(): string
    {
        return $this->property->getName();
    }

    /**
     * Gets the database column name.
     *
     * @return string The column name
     */
    public function getColumnName(): string
    {
        return $this->columnName;
    }

    /**
     * Gets the original value before anonymization.
     *
     * @return mixed The original value
     */
    public function getOriginalValue(): mixed
    {
        return $this->originalValue;
    }

    /**
     * Gets the anonymized value.
     *
     * @return mixed The anonymized value
     */
    public function getAnonymizedValue(): mixed
    {
        return $this->anonymizedValue;
    }

    /**
     * Sets the anonymized value.
     *
     * Listeners can modify the anonymized value by calling this method.
     *
     * @param mixed $anonymizedValue The new anonymized value
     */
    public function setAnonymizedValue(mixed $anonymizedValue): void
    {
        $this->anonymizedValue = $anonymizedValue;
    }

    /**
     * Gets the full database record.
     *
     * @return array<string, mixed> The full database record
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    /**
     * Checks if anonymization should be skipped for this property.
     *
     * @return bool True if anonymization should be skipped, false otherwise
     */
    public function shouldSkipAnonymization(): bool
    {
        return $this->skipAnonymization;
    }

    /**
     * Sets whether to skip anonymization for this property.
     *
     * @param bool $skip True to skip anonymization, false otherwise
     */
    public function setSkipAnonymization(bool $skip): void
    {
        $this->skipAnonymization = $skip;
    }

    /**
     * Checks if this is a dry run.
     *
     * @return bool True if this is a dry run, false otherwise
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }
}
