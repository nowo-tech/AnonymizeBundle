<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Enum\FakerType;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * LogEntry entity demonstrating truncate without explicit order.
 *
 * This entity shows how to use truncate without specifying truncate_order.
 * When truncate_order is null, the table will be truncated in alphabetical
 * order after all tables with explicit orders.
 *
 * Note: For polymorphic entities (Doctrine STI/CTI), truncate only deletes
 * rows matching that entity's discriminator value.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'log_entries')]
#[Anonymize(
    truncate: true,  // This table will be emptied before anonymization
    truncate_order: null  // No explicit order - will be truncated alphabetically after explicit orders
)]
class LogEntry
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[AnonymizeProperty(type: FakerType::TEXT, weight: 1, options: ['type' => 'sentence'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[AnonymizeProperty(type: FakerType::IP_ADDRESS, weight: 2)]
    #[ORM\Column(type: Types::STRING, length: 45)]
    private ?string $ipAddress = null;

    #[AnonymizeProperty(type: FakerType::DATE, weight: 3, options: ['type' => 'past', 'min_date' => '-1 year'])]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $loggedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getLoggedAt(): ?\DateTimeImmutable
    {
        return $this->loggedAt;
    }

    public function setLoggedAt(?\DateTimeImmutable $loggedAt): static
    {
        $this->loggedAt = $loggedAt;

        return $this;
    }
}
