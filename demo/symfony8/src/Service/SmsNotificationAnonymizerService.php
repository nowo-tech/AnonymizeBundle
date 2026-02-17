<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\EntityAnonymizerServiceInterface;

/**
 * Example: anonymize SmsNotification records via a custom service instead of AnonymizeProperty.
 *
 * When an entity uses #[Anonymize(anonymizeService: ...)], the bundle calls this service
 * for each record. Useful for polymorphic entities or when logic is complex.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class SmsNotificationAnonymizerService implements EntityAnonymizerServiceInterface
{
    public function __construct(
        private readonly FakerFactory $fakerFactory
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function supportsBatch(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function anonymize(
        EntityManagerInterface $em,
        ClassMetadata $metadata,
        array $record,
        bool $dryRun
    ): array {
        $phoneFaker = $this->fakerFactory->create('phone', null);
        $textFaker  = $this->fakerFactory->create('text', null);

        return [
            'recipient' => $phoneFaker->generate([]),
            'message'   => $textFaker->generate(['type' => 'sentence', 'maxNbWords' => 12]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function anonymizeBatch(
        EntityManagerInterface $em,
        ClassMetadata $metadata,
        array $records,
        bool $dryRun
    ): array {
        $result = [];
        foreach ($records as $index => $record) {
            $updates = $this->anonymize($em, $metadata, $record, $dryRun);
            if (!empty($updates)) {
                $result[$index] = $updates;
            }
        }

        return $result;
    }
}
