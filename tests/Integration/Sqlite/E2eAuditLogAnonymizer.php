<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Sqlite;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nowo\AnonymizeBundle\Service\EntityAnonymizerServiceInterface;

/**
 * Record-by-record entity anonymizer used by the SQLite end-to-end tests.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class E2eAuditLogAnonymizer implements EntityAnonymizerServiceInterface
{
    public function supportsBatch(): bool
    {
        return false;
    }

    public function anonymize(EntityManagerInterface $em, ClassMetadata $metadata, array $record, bool $dryRun): array
    {
        return ['message' => 'redacted #' . $record['id']];
    }

    public function anonymizeBatch(EntityManagerInterface $em, ClassMetadata $metadata, array $records, bool $dryRun): array
    {
        return [];
    }
}
