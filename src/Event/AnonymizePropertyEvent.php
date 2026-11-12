<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use LogicException;
use Nowo\AnonymizeBundle\Event\Internal\EventOrmState;
use Nowo\AnonymizeBundle\Internal\AnonymizeTargetFactory;
use Nowo\AnonymizeBundle\Metadata\AnonymizeTarget;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Contracts\EventDispatcher\Event;

use function trigger_deprecation;

/**
 * Event dispatched before anonymizing a specific property.
 *
 * This event is dispatched for each property that is about to be anonymized,
 * allowing listeners to modify the anonymized value or skip anonymization.
 *
 * Since 1.6.0 the event carries scalars and the ORM-free {@see AnonymizeTarget} (REQ-ARCH-001 rule 3):
 * {@see getEntityClass()}, {@see getTableName()}, {@see getPropertyName()}, {@see getFieldName()},
 * {@see getColumnName()}, {@see getTarget()}. Build it with {@see create()}; the constructor,
 * {@see getEntityManager()}, {@see getMetadata()} and {@see getProperty()} are deprecated and removed in 2.0.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AnonymizePropertyEvent extends Event
{
    /**
     * @var bool Whether to skip anonymization of this property
     */
    private bool $skipAnonymization = false;

    private AnonymizeTarget $target;

    private string $propertyName;

    private string $fieldName;

    /**
     * Creates a new AnonymizePropertyEvent instance.
     *
     * @deprecated since 1.6.0, use {@see create()}; the constructor takes the arguments of create() in 2.0
     *
     * @param EntityManagerInterface $entityManager The entity manager
     * @param ClassMetadata<object> $metadata The entity metadata
     * @param ReflectionProperty $property The property reflection
     * @param string $columnName The database column name
     * @param mixed $originalValue The original value before anonymization
     * @param mixed $anonymizedValue The anonymized value
     * @param array<string, mixed> $record The full database record
     * @param bool $dryRun Whether this is a dry run
     */
    public function __construct(EntityManagerInterface $entityManager, ClassMetadata $metadata, ReflectionProperty $property, private string $columnName, private mixed $originalValue, private mixed $anonymizedValue, private array $record, private bool $dryRun = false)
    {
        trigger_deprecation('nowo-tech/anonymize-bundle', '1.6', 'Constructing "%s" with Doctrine objects is deprecated, use "%s::create()" instead.', self::class, self::class);

        $this->target       = AnonymizeTargetFactory::fromMetadata($metadata);
        $this->propertyName = $property->getName();
        $this->fieldName    = $property->getName();
        EventOrmState::attach($this, $entityManager, $metadata, null, $property);
    }

    /**
     * Framework-free factory (since 1.6.0).
     *
     * @param AnonymizeTarget $target The class and table of the row
     * @param string $propertyName The PHP property name (for an embedded value object field: the nested property)
     * @param string $columnName The database column name
     * @param mixed $originalValue The original value before anonymization
     * @param mixed $anonymizedValue The anonymized value
     * @param array<string, mixed> $record The full database record
     * @param bool $dryRun Whether this is a dry run
     * @param string|null $fieldName The mapped field name (`embedProperty.field` for an embedded value object); defaults to $propertyName
     */
    public static function create(
        AnonymizeTarget $target,
        string $propertyName,
        string $columnName,
        mixed $originalValue,
        mixed $anonymizedValue,
        array $record,
        bool $dryRun = false,
        ?string $fieldName = null,
    ): self {
        $event                  = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $event->target          = $target;
        $event->propertyName    = $propertyName;
        $event->fieldName       = $fieldName ?? $propertyName;
        $event->columnName      = $columnName;
        $event->originalValue   = $originalValue;
        $event->anonymizedValue = $anonymizedValue;
        $event->record          = $record;
        $event->dryRun          = $dryRun;

        return $event;
    }

    /**
     * Gets the entity manager.
     *
     * @deprecated since 1.6.0, inject your own connection; removed in 2.0
     *
     * @throws LogicException When the event was built with {@see create()} without Doctrine objects
     *
     * @return EntityManagerInterface The entity manager
     */
    public function getEntityManager(): EntityManagerInterface
    {
        trigger_deprecation('nowo-tech/anonymize-bundle', '1.6', 'Calling "%s()" is deprecated, use "getTarget()" (or inject your own connection) instead.', __METHOD__);

        return EventOrmState::entityManager($this) ?? throw new LogicException('This event carries no entity manager (built with create()); use getTarget().');
    }

    /**
     * Gets the entity metadata.
     *
     * @deprecated since 1.6.0, use {@see getTarget()}, {@see getEntityClass()} or {@see getTableName()}; removed in 2.0
     *
     * @throws LogicException When the event was built with {@see create()} without Doctrine objects
     *
     * @return ClassMetadata<object> The entity metadata
     */
    public function getMetadata(): ClassMetadata
    {
        trigger_deprecation('nowo-tech/anonymize-bundle', '1.6', 'Calling "%s()" is deprecated, use "getTarget()" instead.', __METHOD__);

        return EventOrmState::metadata($this) ?? throw new LogicException('This event carries no class metadata (built with create()); use getTarget().');
    }

    /**
     * Gets the property reflection.
     *
     * @deprecated since 1.6.0, use {@see getPropertyName()} or {@see getFieldName()}; removed in 2.0
     *
     * @throws LogicException When the event was built with {@see create()} without Doctrine objects
     *
     * @return ReflectionProperty The property reflection
     */
    public function getProperty(): ReflectionProperty
    {
        trigger_deprecation('nowo-tech/anonymize-bundle', '1.6', 'Calling "%s()" is deprecated, use "getPropertyName()" or "getFieldName()" instead.', __METHOD__);

        return EventOrmState::property($this) ?? throw new LogicException('This event carries no property reflection (built with create()); use getPropertyName().');
    }

    /**
     * The class and table of the row (ORM-free, since 1.6.0).
     */
    public function getTarget(): AnonymizeTarget
    {
        return $this->target;
    }

    /**
     * The anonymized class (since 1.6.0).
     */
    public function getEntityClass(): string
    {
        return $this->target->getEntityClass();
    }

    /**
     * The table of the row (since 1.6.0).
     */
    public function getTableName(): string
    {
        return $this->target->getTableName();
    }

    /**
     * Gets the property name.
     *
     * @return string The property name
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * The mapped field name (since 1.6.0): `embedProperty.field` for an embedded value object field, otherwise the
     * property name.
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
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
        // @igor-ignore - Request-scoped Event DTO; not a shared worker service.
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
        // @igor-ignore - Request-scoped Event DTO; not a shared worker service.
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
