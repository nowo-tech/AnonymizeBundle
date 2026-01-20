<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Event;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after anonymization completes.
 *
 * This event is dispatched once at the end of the anonymization process,
 * after all entities have been processed.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AfterAnonymizeEvent extends Event
{
    /**
     * Creates a new AfterAnonymizeEvent instance.
     *
     * @param EntityManagerInterface $entityManager The entity manager
     * @param array<string> $entityClasses Array of entity class names that were anonymized
     * @param int $totalProcessed Total number of records processed
     * @param int $totalUpdated Total number of records updated
     * @param bool $dryRun Whether this was a dry run
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly array $entityClasses,
        private readonly int $totalProcessed,
        private readonly int $totalUpdated,
        private readonly bool $dryRun = false
    ) {}

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
