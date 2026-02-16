<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * Invoice entity demonstrating masking and company anonymization.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'invoices')]
#[Anonymize]
class Invoice
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[AnonymizeProperty(type: 'service', service: 'App\Service\CustomReferenceFaker', weight: 1, options: ['prefix' => 'INV', 'length' => 12, 'separator' => '-'])]
    private ?string $invoiceNumber = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'company', weight: 2, options: ['type' => 'corporation'])]
    private ?string $companyName = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'address', weight: 3, options: ['format' => 'full', 'include_postal_code' => true])]
    private ?string $companyAddress = null;

    #[ORM\Column(length: 34, nullable: true)]
    #[AnonymizeProperty(type: 'iban', weight: 4, options: ['country' => 'ES'])]
    private ?string $bankAccount = null;

    #[ORM\Column(length: 19, nullable: true)]
    #[AnonymizeProperty(
        type: 'masking',
        weight: 5,
        options: [
            'preserve_start' => 4,
            'preserve_end' => 4,
            'mask_char' => '*',
        ]
    )]
    private ?string $creditCard = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[AnonymizeProperty(type: 'age', weight: 6, options: ['min' => 100, 'max' => 10000])]
    private ?string $amount = null;

    #[ORM\Column(type: 'datetime')]
    #[AnonymizeProperty(type: 'date', weight: 7, options: ['type' => 'past', 'min_date' => '-6 months', 'max_date' => 'now', 'format' => 'Y-m-d H:i:s'])]
    private ?\DateTimeInterface $issueDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[AnonymizeProperty(type: 'date', weight: 8, options: ['type' => 'future', 'min_date' => 'now', 'max_date' => '+30 days', 'format' => 'Y-m-d H:i:s'])]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getCompanyAddress(): ?string
    {
        return $this->companyAddress;
    }

    public function setCompanyAddress(?string $companyAddress): static
    {
        $this->companyAddress = $companyAddress;

        return $this;
    }

    public function getBankAccount(): ?string
    {
        return $this->bankAccount;
    }

    public function setBankAccount(?string $bankAccount): static
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }

    public function getCreditCard(): ?string
    {
        return $this->creditCard;
    }

    public function setCreditCard(?string $creditCard): static
    {
        $this->creditCard = $creditCard;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getIssueDate(): ?\DateTimeInterface
    {
        return $this->issueDate;
    }

    public function setIssueDate(?\DateTimeInterface $issueDate): static
    {
        $this->issueDate = $issueDate;

        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;

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
}
