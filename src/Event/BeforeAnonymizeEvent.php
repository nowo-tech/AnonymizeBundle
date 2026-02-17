<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Event;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before anonymization starts.
 *
 * This event is dispatched once at the beginning of the anonymization process,
 * before any entities are processed.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class BeforeAnonymizeEvent extends Event
{
    /**
     * Creates a new BeforeAnonymizeEvent instance.
     *
     * @param EntityManagerInterface $entityManager The entity manager
     * @param array<string> $entityClasses Array of entity class names to be anonymized
     * @param bool $dryRun Whether this is a dry run
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private array $entityClasses,
        private readonly bool $dryRun = false
    ) {
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
