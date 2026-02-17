<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * EmailSubscription entity demonstrating pattern-based anonymization.
 *
 * This entity shows how to use patterns to anonymize emails based on domain,
 * subscription status, or other criteria. Useful for GDPR compliance scenarios
 * where you need to anonymize specific subsets of data.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'email_subscriptions')]
#[Anonymize]
class EmailSubscription
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(
        type: 'email',
        weight: 1,
        // Only anonymize emails from specific domains (e.g., internal test domains)
        // Uses | (OR) operator to match multiple domains
        includePatterns: ['email' => '%@test-domain.com|%@example.com|%@demo.local'],
        options: ['domain' => 'anonymized.local', 'format' => 'random'],
    )]
    private ?string $email = null;

    #[ORM\Column(length: 100)]
    #[AnonymizeProperty(type: 'name', weight: 2)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null; // 'active', 'inactive', 'unsubscribed'

    #[ORM\Column(length: 100, nullable: true)]
    #[AnonymizeProperty(
        type: 'email',
        weight: 3,
        // Only anonymize if status is 'inactive' or 'unsubscribed'
        // Uses | (OR) operator to match multiple status values
        includePatterns: ['status' => 'inactive|unsubscribed'],
        options: ['domain' => 'removed.local', 'format' => 'random'],
    )]
    private ?string $backupEmail = null;

    #[ORM\Column(type: 'datetime')]
    #[AnonymizeProperty(type: 'date', weight: 4, options: ['type' => 'past', 'min_date' => '-2 years', 'max_date' => 'now', 'format' => 'Y-m-d H:i:s'])]
    private ?DateTimeInterface $subscribedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[AnonymizeProperty(
        type: 'date',
        weight: 5,
        // Only anonymize unsubscribed dates (not active subscriptions)
        includePatterns: ['status' => 'unsubscribed'],
        options: ['type' => 'past', 'min_date' => '-1 year', 'max_date' => 'now', 'format' => 'Y-m-d H:i:s'],
    )]
    private ?DateTimeInterface $unsubscribedAt = null;

    #[ORM\Column(length: 50)]
    private ?string $source = null; // 'website', 'newsletter', 'promotion', 'partner'

    #[ORM\Column(type: 'text', nullable: true)]
    #[AnonymizeProperty(
        type: 'text',
        weight: 6,
        // Only anonymize notes for inactive/unsubscribed users
        includePatterns: ['status' => 'inactive|unsubscribed'],
        options: ['type' => 'sentence', 'min_words' => 5, 'max_words' => 15],
    )]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getBackupEmail(): ?string
    {
        return $this->backupEmail;
    }

    public function setBackupEmail(?string $backupEmail): static
    {
        $this->backupEmail = $backupEmail;

        return $this;
    }

    public function getSubscribedAt(): ?DateTimeInterface
    {
        return $this->subscribedAt;
    }

    public function setSubscribedAt(?DateTimeInterface $subscribedAt): static
    {
        $this->subscribedAt = $subscribedAt;

        return $this;
    }

    public function getUnsubscribedAt(): ?DateTimeInterface
    {
        return $this->unsubscribedAt;
    }

    public function setUnsubscribedAt(?DateTimeInterface $unsubscribedAt): static
    {
        $this->unsubscribedAt = $unsubscribedAt;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}
