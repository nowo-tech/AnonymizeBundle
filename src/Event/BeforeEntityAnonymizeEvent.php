<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionClass;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before anonymizing a specific entity.
 *
 * This event is dispatched once per entity class before processing its records.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class BeforeEntityAnonymizeEvent extends Event
{
    /**
     * Creates a new BeforeEntityAnonymizeEvent instance.
     *
     * @param EntityManagerInterface $entityManager The entity manager
     * @param ClassMetadata $metadata The entity metadata
     * @param ReflectionClass $reflection The entity reflection class
     * @param int $totalRecords Total number of records for this entity
     * @param bool $dryRun Whether this is a dry run
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClassMetadata $metadata,
        private readonly ReflectionClass $reflection,
        private readonly int $totalRecords,
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
     * Gets the entity metadata.
     *
     * @return ClassMetadata The entity metadata
     */
    public function getMetadata(): ClassMetadata
    {
        return $this->metadata;
    }

    /**
     * Gets the entity reflection class.
     *
     * @return ReflectionClass The entity reflection class
     */
    public function getReflection(): ReflectionClass
    {
        return $this->reflection;
    }

    /**
     * Gets the entity class name.
     *
     * @return string The entity class name
     */
    public function getEntityClass(): string
    {
        return $this->metadata->getName();
    }

    /**
     * Gets the total number of records for this entity.
     *
     * @return int Total number of records
     */
    public function getTotalRecords(): int
    {
        return $this->totalRecords;
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
