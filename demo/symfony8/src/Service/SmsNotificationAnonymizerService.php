<?php

declare(strict_types=1);

namespace App\Service;

use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Metadata\AnonymizeTarget;
use Nowo\AnonymizeBundle\Service\RowAnonymizerInterface;

/**
 * Example: anonymize SmsNotification records via a custom service instead of AnonymizeProperty.
 *
 * When an entity uses #[Anonymize(anonymizeService: ...)], the bundle calls this service
 * for each record. Useful for polymorphic entities or when logic is complex.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class SmsNotificationAnonymizerService implements RowAnonymizerInterface
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
    public function anonymizeRow(AnonymizeTarget $target, array $record, bool $dryRun): array
    {
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
    public function anonymizeRows(AnonymizeTarget $target, array $records, bool $dryRun): array
    {
        $result = [];
        foreach ($records as $index => $record) {
            $updates = $this->anonymizeRow($target, $record, $dryRun);
            if (!empty($updates)) {
                $result[$index] = $updates;
            }
        }

        return $result;
    }
}
