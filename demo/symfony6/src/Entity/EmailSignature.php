<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * EmailSignature entity demonstrating HTML faker for anonymizing email signatures.
 *
 * This entity shows how to use the HTML faker to anonymize email signatures
 * and other HTML content with lorem ipsum text.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'email_signatures')]
#[Anonymize]
class EmailSignature
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'email', weight: 1)]
    private ?string $email = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[AnonymizeProperty(
        type: 'html',
        weight: 2,
        options: [
            'type' => 'signature',
            'include_links' => true,
            'include_styles' => false,
        ]
    )]
    private ?string $signature = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[AnonymizeProperty(
        type: 'html',
        weight: 3,
        options: [
            'type' => 'paragraph',
            'min_paragraphs' => 1,
            'max_paragraphs' => 3,
            'include_links' => true,
        ]
    )]
    private ?string $emailBody = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[AnonymizeProperty(type: 'name', weight: 4)]
    private ?string $senderName = null;

    #[ORM\Column(type: 'datetime')]
    #[AnonymizeProperty(type: 'date', weight: 5, options: ['type' => 'past', 'min_date' => '-1 year', 'max_date' => 'now'])]
    private ?\DateTimeInterface $sentAt = null;

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

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): static
    {
        $this->signature = $signature;

        return $this;
    }

    public function getEmailBody(): ?string
    {
        return $this->emailBody;
    }

    public function setEmailBody(?string $emailBody): static
    {
        $this->emailBody = $emailBody;

        return $this;
    }

    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    public function setSenderName(?string $senderName): static
    {
        $this->senderName = $senderName;

        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }
}
