<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Interface for custom entity anonymization via a service.
 *
 * When an entity has #[Anonymize(anonymizeService: 'your_service_id')], the bundle
 * calls this service for each record instead of applying AnonymizeProperty attributes.
 * Useful for polymorphic entities or when logic is complex (API calls, external rules, etc.).
 *
 * The service receives the raw DB record (column names as keys) and returns
 * an associative array of column => new value to apply. Only returned columns are updated.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
interface EntityAnonymizerServiceInterface
{
    /**
     * Anonymize a single record. Return columns to update.
     *
     * @param EntityManagerInterface $em Entity manager (for the connection in use)
     * @param ClassMetadata $metadata Class metadata for the entity (concrete class, e.g. child in STI)
     * @param array<string, mixed> $record Raw record from DB (column names as keys)
     * @param bool $dryRun If true, do not perform side effects; only return what would be updated
     * @return array<string, mixed> Map of column name => new value (only columns to update)
     */
    public function anonymize(
        EntityManagerInterface $em,
        ClassMetadata $metadata,
        array $record,
        bool $dryRun
    ): array;
}
