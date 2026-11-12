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
use Symfony\Contracts\EventDispatcher\Event;

use function trigger_deprecation;

/**
 * Event dispatched after anonymizing a specific entity.
 *
 * This event is dispatched once per entity class after processing its records.
 *
 * Since 1.6.0 the event carries scalars and the ORM-free {@see AnonymizeTarget} (REQ-ARCH-001 rule 3). Build it with
 * {@see create()}; the constructor, {@see getEntityManager()}, {@see getMetadata()} and {@see getReflection()} are
 * deprecated and removed in 2.0.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AfterEntityAnonymizeEvent extends Event
{
    /** Null until first read for an event built with the deprecated constructor (built from the metadata then). */
    private ?AnonymizeTarget $target = null;

    /**
     * Creates a new AfterEntityAnonymizeEvent instance.
     *
     * @deprecated since 1.6.0, use {@see create()}; the constructor takes the arguments of create() in 2.0
     *
     * @param EntityManagerInterface $entityManager The entity manager
     * @param ClassMetadata<object> $metadata The entity metadata
     * @param ReflectionClass<object> $reflection The entity reflection class
     * @param int $processed Number of records processed
     * @param int $updated Number of records updated
     * @param array<string, int> $propertyStats Statistics per property
     * @param bool $dryRun Whether this was a dry run
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ClassMetadata $metadata,
        ReflectionClass $reflection,
        private int $processed,
        private int $updated,
        private array $propertyStats,
        private bool $dryRun = false
    ) {
        trigger_deprecation('nowo-tech/anonymize-bundle', '1.6', 'Constructing "%s" with Doctrine objects is deprecated, use "%s::create()" instead.', self::class, self::class);

        EventOrmState::attach($this, $entityManager, $metadata, $reflection);
    }

    /**
     * Framework-free factory (since 1.6.0).
     *
     * @param AnonymizeTarget $target The class and table that were anonymized
     * @param int $processed Number of records processed
     * @param int $updated Number of records updated
     * @param array<string, int> $propertyStats Statistics per property
     * @param bool $dryRun Whether this was a dry run
     */
    public static function create(AnonymizeTarget $target, int $processed, int $updated, array $propertyStats, bool $dryRun = false): self
    {
        $event                = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $event->target        = $target;
        $event->processed     = $processed;
        $event->updated       = $updated;
        $event->propertyStats = $propertyStats;
        $event->dryRun        = $dryRun;

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
     * Gets the entity reflection class.
     *
     * @deprecated since 1.6.0, use {@see getEntityClass()}; removed in 2.0
     *
     * @throws LogicException When the event was built with {@see create()} without Doctrine objects
     *
     * @return ReflectionClass<object> The entity reflection class
     */
    public function getReflection(): ReflectionClass
    {
        trigger_deprecation('nowo-tech/anonymize-bundle', '1.6', 'Calling "%s()" is deprecated, use "getEntityClass()" instead.', __METHOD__);

        return EventOrmState::reflection($this) ?? throw new LogicException('This event carries no reflection class (built with create()); use getEntityClass().');
    }

    /**
     * The class and table that were anonymized (ORM-free, since 1.6.0).
     */
    public function getTarget(): AnonymizeTarget
    {
        // @igor-ignore - Request-scoped Event DTO; built once from the metadata of a deprecated-constructor event.
        return $this->target ??= AnonymizeTargetFactory::fromMetadata(EventOrmState::metadata($this) ?? throw new LogicException('This event carries neither a target nor class metadata.'));
    }

    /**
     * Gets the entity class name.
     *
     * @return string The entity class name
     */
    public function getEntityClass(): string
    {
        return $this->getTarget()->getEntityClass();
    }

    /**
     * The table the rows were read from and written to (since 1.6.0).
     */
    public function getTableName(): string
    {
        return $this->getTarget()->getTableName();
    }

    /**
     * Gets the number of records processed.
     *
     * @return int Number of records processed
     */
    public function getProcessed(): int
    {
        return $this->processed;
    }

    /**
     * Gets the number of records updated.
     *
     * @return int Number of records updated
     */
    public function getUpdated(): int
    {
        return $this->updated;
    }

    /**
     * Gets the statistics per property.
     *
     * @return array<string, int> Statistics per property (property name => count)
     */
    public function getPropertyStats(): array
    {
        return $this->propertyStats;
    }

    /**
     * Checks if this was a dry run.
     *
     * @return bool True if this was a dry run, false otherwise
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }
}
