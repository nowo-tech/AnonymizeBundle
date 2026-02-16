<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Enum\FakerType;

/**
 * Email notification (polymorphic STI child).
 *
 * With truncate=true, only rows where type='email' are deleted when anonymization
 * runs — not the whole notifications table. truncate_order: 1 runs before SmsNotification.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\DiscriminatorValue('email')]
#[Anonymize(
    truncate: true,
    truncate_order: 1
)]
class EmailNotification extends AbstractNotification
{
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[AnonymizeProperty(type: FakerType::EMAIL, weight: 1)]
    private ?string $recipient = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[AnonymizeProperty(type: FakerType::TEXT, weight: 2, options: ['type' => 'sentence', 'maxNbWords' => 6])]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    #[AnonymizeProperty(type: FakerType::TEXT, weight: 3, options: ['type' => 'paragraph'])]
    private ?string $body = null;

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function setRecipient(string $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }
}
