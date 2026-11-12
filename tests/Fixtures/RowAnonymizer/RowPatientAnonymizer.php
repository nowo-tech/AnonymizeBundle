<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer;

use Nowo\AnonymizeBundle\Metadata\AnonymizeTarget;
use Nowo\AnonymizeBundle\Service\RowAnonymizerInterface;

/**
 * {@see PatientRedaction} on the ORM-free row SPI only (table and key from the AnonymizeTarget); no Doctrine import.
 */
final readonly class RowPatientAnonymizer implements RowAnonymizerInterface
{
    public function __construct(public CallLog $log = new CallLog(), private bool $batch = false)
    {
    }

    public function supportsBatch(): bool
    {
        return $this->batch;
    }

    public function anonymizeRow(AnonymizeTarget $target, array $record, bool $dryRun): array
    {
        return $this->log->record('row', $target->getTableName(), $dryRun, $record, PatientRedaction::row($target->getTableName(), $target->getIdentifierColumns(), $record));
    }

    public function anonymizeRows(AnonymizeTarget $target, array $records, bool $dryRun): array
    {
        return $this->log->record('rows', $target->getTableName(), $dryRun, $records, PatientRedaction::rows($target->getTableName(), $target->getIdentifierColumns(), $records));
    }
}
