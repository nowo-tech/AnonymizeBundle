<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use LogicException;
use Nowo\AnonymizeBundle\Metadata\AnonymizeTarget;
use Nowo\AnonymizeBundle\Service\EntityAnonymizerServiceInterface;
use Nowo\AnonymizeBundle\Service\RowAnonymizerInterface;

/**
 * Implements both SPIs; the deprecated methods throw, proving that the bundle calls the row SPI.
 */
final readonly class DualPatientAnonymizer implements RowAnonymizerInterface, EntityAnonymizerServiceInterface
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

    public function anonymize(EntityManagerInterface $em, ClassMetadata $metadata, array $record, bool $dryRun): array
    {
        throw new LogicException('The deprecated SPI must not be called when the row SPI is implemented.');
    }

    public function anonymizeBatch(EntityManagerInterface $em, ClassMetadata $metadata, array $records, bool $dryRun): array
    {
        throw new LogicException('The deprecated SPI must not be called when the row SPI is implemented.');
    }
}
