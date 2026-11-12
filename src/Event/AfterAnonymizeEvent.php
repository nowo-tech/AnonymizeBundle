<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Event;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Nowo\AnonymizeBundle\Event\Internal\EventOrmState;
use ReflectionClass;
use Symfony\Contracts\EventDispatcher\Event;

use function trigger_deprecation;

/**
 * Event dispatched after anonymization completes.
 *
 * This event is dispatched once at the end of the anonymization process,
 * after all entities have been processed.
 *
 * Since 1.6.0 the event carries scalars only (REQ-ARCH-001 rule 3). Build it with {@see create()}; the constructor
 * and {@see getEntityManager()} are deprecated and removed in 2.0.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AfterAnonymizeEvent extends Event
{
    private ?string $entityManagerName = null;

    /**
     * Creates a new AfterAnonymizeEvent instance.
     *
     * @deprecated since 1.6.0, use {@see create()}; the constructor takes the arguments of create() in 2.0
     *
     * @param EntityManagerInterface $entityManager The entity manager
     * @param array<string> $entityClasses Array of entity class names that were anonymized
     * @param int $totalProcessed Total number of records processed
     * @param int $totalUpdated Total number of records updated
     * @param bool $dryRun Whether this was a dry run
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        private array $entityClasses,
        private int $totalProcessed,
        private int $totalUpdated,
        private bool $dryRun = false
    ) {
        trigger_deprecation('nowo-tech/anonymize-bundle', '1.6', 'Constructing "%s" with an entity manager is deprecated, use "%s::create()" instead.', self::class, self::class);
        EventOrmState::attach($this, $entityManager);
    }

    /**
     * Framework-free factory (since 1.6.0).
     *
     * @param array<string> $entityClasses Entity class names that were anonymized
     * @param int $totalProcessed Total number of records processed
     * @param int $totalUpdated Total number of records updated
     * @param bool $dryRun Whether this was a dry run
     * @param string|null $entityManagerName The Doctrine entity manager (connection) the run worked on, when known
     */
    public static function create(array $entityClasses, int $totalProcessed, int $totalUpdated, bool $dryRun = false, ?string $entityManagerName = null): self
    {
        $event                    = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $event->entityClasses     = $entityClasses;
        $event->totalProcessed    = $totalProcessed;
        $event->totalUpdated      = $totalUpdated;
        $event->dryRun            = $dryRun;
        $event->entityManagerName = $entityManagerName;

        return $event;
    }

    /**
     * Gets the entity manager.
     *
     * @deprecated since 1.6.0, use {@see getEntityManagerName()} (or inject your own connection); removed in 2.0
     *
     * @throws LogicException When the event was built with {@see create()} without an entity manager
     *
     * @return EntityManagerInterface The entity manager
     */
    public function getEntityManager(): EntityManagerInterface
    {
        trigger_deprecation('nowo-tech/anonymize-bundle', '1.6', 'Calling "%s()" is deprecated, use "getEntityManagerName()" instead.', __METHOD__);

        return EventOrmState::entityManager($this) ?? throw new LogicException('This event carries no entity manager (built with create()); use getEntityManagerName().');
    }

    /**
     * Name of the Doctrine entity manager (connection) the run worked on; null when the event was built without it.
     *
     * @since 1.6.0
     */
    public function getEntityManagerName(): ?string
    {
        return $this->entityManagerName;
    }

    /**
     * Gets the entity classes that were anonymized.
     *
     * @return array<string> Array of entity class names
     */
    public function getEntityClasses(): array
    {
        return $this->entityClasses;
    }

    /**
     * Gets the total number of records processed.
     *
     * @return int Total number of records processed
     */
    public function getTotalProcessed(): int
    {
        return $this->totalProcessed;
    }

    /**
     * Gets the total number of records updated.
     *
     * @return int Total number of records updated
     */
    public function getTotalUpdated(): int
    {
        return $this->totalUpdated;
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
