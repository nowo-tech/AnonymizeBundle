<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\DependencyInjection\Compiler\Fixtures;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nowo\AnonymizeBundle\Service\EntityAnonymizerServiceInterface;

/**
 * Entity anonymizer service (picked up whatever its visibility).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class EntityAnonymizer implements EntityAnonymizerServiceInterface
{
    public function supportsBatch(): bool
    {
        return false;
    }

    public function anonymize(EntityManagerInterface $em, ClassMetadata $metadata, array $record, bool $dryRun): array
    {
        return [];
    }

    public function anonymizeBatch(EntityManagerInterface $em, ClassMetadata $metadata, array $records, bool $dryRun): array
    {
        return [];
    }
}
