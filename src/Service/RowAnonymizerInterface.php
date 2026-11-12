<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Nowo\AnonymizeBundle\Metadata\AnonymizeTarget;

/**
 * ORM-free SPI for custom entity anonymization via a service (since 1.6, REQ-ARCH-001 rule 3).
 *
 * The VO twin of {@see EntityAnonymizerServiceInterface}: the bundle passes an {@see AnonymizeTarget} (class, table,
 * key and field columns) instead of the entity manager and the Doctrine class metadata, so a DDD / hexagonal host can
 * implement it without Doctrine types. Select it exactly as before: `#[Anonymize(anonymizeService: 'service_id')]` or
 * `nowo_anonymize.entities.<FQCN>.anonymize_service`.
 *
 * The bundle treats the result exactly as the result of the deprecated interface: the same rows are read (same
 * chunks, same order), the same inclusion / exclusion patterns filter them, the returned columns are written by the
 * same UPDATE in the same per-chunk transaction, and nothing is written in a dry run. A service that implements both
 * interfaces is called through this one.
 *
 * Implementations must be stateless between runs (FrankenPHP worker mode without kernel reset) and must not open,
 * commit or roll back transactions on the bundle's connection. A service that needs the database injects its own
 * connection.
 *
 * @since 1.6.0
 */
interface RowAnonymizerInterface
{
    /**
     * Whether this service supports batch anonymization.
     * When true, the bundle calls {@see anonymizeRows()} per chunk; when false, {@see anonymizeRow()} per record.
     */
    public function supportsBatch(): bool;

    /**
     * Anonymize a single record. Return columns to update.
     *
     * @param AnonymizeTarget $target The class and table of the record (the concrete class, e.g. a child in STI)
     * @param array<string, mixed> $record Raw record from DB (column names as keys)
     * @param bool $dryRun If true, do not perform side effects; only return what would be updated
     *
     * @return array<string, mixed> Map of column name => new value (only columns to update)
     */
    public function anonymizeRow(AnonymizeTarget $target, array $record, bool $dryRun): array;

    /**
     * Anonymize a batch of records. Called only when supportsBatch() returns true.
     *
     * @param AnonymizeTarget $target The class and table of the records
     * @param array<int, array<string, mixed>> $records List of raw records (same order as chunk)
     * @param bool $dryRun If true, do not perform side effects; only return what would be updated
     *
     * @return array<int, array<string, mixed>> Map of record index => (column => new value). Omit indices that need no update.
     */
    public function anonymizeRows(AnonymizeTarget $target, array $records, bool $dryRun): array;
}
