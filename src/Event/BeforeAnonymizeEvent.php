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
 * Event dispatched before anonymization starts.
 *
 * This event is dispatched once at the beginning of the anonymization process,
 * before any entities are processed.
 *
 * Since 1.6.0 the event carries scalars only (REQ-ARCH-001 rule 3): {@see getEntityClasses()},
 * {@see getEntityManagerName()} and {@see isDryRun()}. Build it with {@see create()}; the constructor and
 * {@see getEntityManager()} are deprecated and removed in 2.0.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class BeforeAnonymizeEvent extends Event
{
    private ?string $entityManagerName = null;

    /**
     * Creates a new BeforeAnonymizeEvent instance.
     *
     * @deprecated since 1.6.0, use {@see create()}; the constructor takes the arguments of create() in 2.0
     *
     * @param EntityManagerInterface $entityManager The entity manager
     * @param array<string> $entityClasses Array of entity class names to be anonymized
     * @param bool $dryRun Whether this is a dry run
     */
    public function __construct(EntityManagerInterface $entityManager, private array $entityClasses, private bool $dryRun = false)
    {
        trigger_deprecation('nowo-tech/anonymize-bundle', '1.6', 'Constructing "%s" with an entity manager is deprecated, use "%s::create()" instead.', self::class, self::class);
        EventOrmState::attach($this, $entityManager);
    }

    /**
     * Framework-free factory (since 1.6.0).
     *
     * @param array<string> $entityClasses Entity class names to be anonymized
     * @param bool $dryRun Whether this is a dry run
     * @param string|null $entityManagerName The Doctrine entity manager (connection) the run works on, when known
     */
    public static function create(array $entityClasses, bool $dryRun = false, ?string $entityManagerName = null): self
    {
        $event                    = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $event->entityClasses     = $entityClasses;
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
     * Name of the Doctrine entity manager (connection) the run works on; null when the event was built without it.
     *
     * @since 1.6.0
     */
    public function getEntityManagerName(): ?string
    {
        return $this->entityManagerName;
    }

    /**
     * Gets the entity classes to be anonymized.
     *
     * @return array<string> Array of entity class names
     */
    public function getEntityClasses(): array
    {
        return $this->entityClasses;
    }

    /**
     * Sets the entity classes to be anonymized.
     *
     * @param array<string> $entityClasses Array of entity class names
     */
    public function setEntityClasses(array $entityClasses): void
    {
        // @igor-ignore - Request-scoped Event DTO; not a shared worker service.
        $this->entityClasses = $entityClasses;
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
