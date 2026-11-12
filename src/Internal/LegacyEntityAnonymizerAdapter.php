<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Internal;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nowo\AnonymizeBundle\Metadata\AnonymizeTarget;
use Nowo\AnonymizeBundle\Service\EntityAnonymizerServiceInterface;
use Nowo\AnonymizeBundle\Service\RowAnonymizerInterface;

/**
 * Calls a service that implements only the deprecated {@see EntityAnonymizerServiceInterface} through the row SPI.
 *
 * Built per class and per run by AnonymizeService (never shared): it forwards every call unchanged, with the entity
 * manager and the class metadata of that run, so the rows and columns a legacy service anonymizes are exactly those of
 * 1.5.
 *
 * @internal
 *
 * @since 1.6.0
 */
final readonly class LegacyEntityAnonymizerAdapter implements RowAnonymizerInterface
{
    /**
     * @param ClassMetadata<object> $metadata
     */
    public function __construct(
        private EntityAnonymizerServiceInterface $service,
        private EntityManagerInterface $entityManager,
        private ClassMetadata $metadata,
    ) {
    }

    public function supportsBatch(): bool
    {
        return $this->service->supportsBatch();
    }

    public function anonymizeRow(AnonymizeTarget $target, array $record, bool $dryRun): array
    {
        return $this->service->anonymize($this->entityManager, $this->metadata, $record, $dryRun);
    }

    public function anonymizeRows(AnonymizeTarget $target, array $records, bool $dryRun): array
    {
        return $this->service->anonymizeBatch($this->entityManager, $this->metadata, $records, $dryRun);
    }
}
