<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;

/**
 * SMS notification (polymorphic STI child).
 *
 * With truncate=true, only rows where type='sms' are deleted when anonymization runs.
 * Anonymization is done by a custom service (anonymizeService) instead of AnonymizeProperty.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\DiscriminatorValue('sms')]
#[Anonymize(
    anonymizeService: \App\Service\SmsNotificationAnonymizerService::class,
    truncate: true,
    truncate_order: 2,
)]
class SmsNotification extends AbstractNotification
{
    #[ORM\Column(type: Types::STRING, length: 20)]
    private ?string $recipient = null;

    #[ORM\Column(type: Types::STRING, length: 160)]
    private ?string $message = null;

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function setRecipient(string $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }
}
