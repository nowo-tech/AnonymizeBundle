<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Interface for custom entity anonymization via a service.
 *
 * When an entity has #[Anonymize(anonymizeService: 'your_service_id')], the bundle
 * calls this service for each record (or for a batch) instead of applying AnonymizeProperty attributes.
 * Useful for polymorphic entities or when logic is complex (API calls, external rules, etc.).
 *
 * The service can work record-by-record (anonymize) or by batch (anonymizeBatch).
 * When supportsBatch() returns true, the bundle calls anonymizeBatch() per chunk; otherwise it calls anonymize() for each record.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
interface EntityAnonymizerServiceInterface
{
    /**
     * Whether this service supports batch anonymization.
     * When true, the bundle will call anonymizeBatch() per chunk; when false, anonymize() per record.
     */
    public function supportsBatch(): bool;

    /**
     * Anonymize a single record. Return columns to update.
     *
     * @param EntityManagerInterface $em Entity manager (for the connection in use)
     * @param ClassMetadata $metadata Class metadata for the entity (concrete class, e.g. child in STI)
     * @param array<string, mixed> $record Raw record from DB (column names as keys)
     * @param bool $dryRun If true, do not perform side effects; only return what would be updated
     *
     * @return array<string, mixed> Map of column name => new value (only columns to update)
     */
    public function anonymize(
        EntityManagerInterface $em,
        ClassMetadata $metadata,
        array $record,
        bool $dryRun
    ): array;

    /**
     * Anonymize a batch of records. Called only when supportsBatch() returns true.
     *
     * @param EntityManagerInterface $em Entity manager (for the connection in use)
     * @param ClassMetadata $metadata Class metadata for the entity
     * @param array<int, array<string, mixed>> $records List of raw records (same order as chunk)
     * @param bool $dryRun If true, do not perform side effects; only return what would be updated
     *
     * @return array<int, array<string, mixed>> Map of record index => (column => new value). Omit indices that need no update.
     */
    public function anonymizeBatch(
        EntityManagerInterface $em,
        ClassMetadata $metadata,
        array $records,
        bool $dryRun
    ): array;
}
