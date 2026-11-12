<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nowo\AnonymizeBundle\Service\EntityAnonymizerServiceInterface;

/**
 * {@see PatientRedaction} on the deprecated SPI only (table and key from the Doctrine metadata).
 */
final readonly class LegacyPatientAnonymizer implements EntityAnonymizerServiceInterface
{
    public function __construct(public CallLog $log = new CallLog(), private bool $batch = false)
    {
    }

    public function supportsBatch(): bool
    {
        return $this->batch;
    }

    public function anonymize(EntityManagerInterface $em, ClassMetadata $metadata, array $record, bool $dryRun): array
    {
        return $this->log->record('row', $metadata->getTableName(), $dryRun, $record, PatientRedaction::row($metadata->getTableName(), array_values($metadata->getIdentifierColumnNames()), $record));
    }

    public function anonymizeBatch(EntityManagerInterface $em, ClassMetadata $metadata, array $records, bool $dryRun): array
    {
        return $this->log->record('rows', $metadata->getTableName(), $dryRun, $records, PatientRedaction::rows($metadata->getTableName(), array_values($metadata->getIdentifierColumnNames()), $records));
    }
}
