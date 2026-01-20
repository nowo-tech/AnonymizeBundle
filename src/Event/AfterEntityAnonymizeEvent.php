<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionClass;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after anonymizing a specific entity.
 *
 * This event is dispatched once per entity class after processing its records.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AfterEntityAnonymizeEvent extends Event
{
    /**
     * Creates a new AfterEntityAnonymizeEvent instance.
     *
     * @param EntityManagerInterface $entityManager The entity manager
     * @param ClassMetadata $metadata The entity metadata
     * @param ReflectionClass $reflection The entity reflection class
     * @param int $processed Number of records processed
     * @param int $updated Number of records updated
     * @param array<string, int> $propertyStats Statistics per property
     * @param bool $dryRun Whether this was a dry run
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClassMetadata $metadata,
        private readonly ReflectionClass $reflection,
        private readonly int $processed,
        private readonly int $updated,
        private readonly array $propertyStats,
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
