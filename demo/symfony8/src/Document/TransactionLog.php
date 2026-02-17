<?php

declare(strict_types=1);

namespace App\Document;

use DateTimeInterface;

/**
 * TransactionLog document for MongoDB.
 *
 * Demonstrates financial fakers: iban, credit_card, numeric, masking, hash.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class TransactionLog
{
    private ?string $id                         = null;
    private ?string $transactionId              = null;
    private ?string $customerEmail              = null;
    private ?string $iban                       = null;
    private ?string $creditCard                 = null;
    private ?string $maskedCard                 = null; // Last 4 digits visible
    private ?float $amount                      = null;
    private ?string $currency                   = null;
    private ?string $transactionHash            = null;
    private ?string $status                     = null; // 'pending', 'completed', 'failed', 'refunded'
    private ?DateTimeInterface $transactionDate = null;
    private bool $anonymized                    = false;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): static
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(?string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): static
    {
        $this->iban = $iban;

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

    public function getMaskedCard(): ?string
    {
        return $this->maskedCard;
    }

    public function setMaskedCard(?string $maskedCard): static
    {
        $this->maskedCard = $maskedCard;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getTransactionHash(): ?string
    {
        return $this->transactionHash;
    }

    public function setTransactionHash(?string $transactionHash): static
    {
        $this->transactionHash = $transactionHash;

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

    public function getTransactionDate(): ?DateTimeInterface
    {
        return $this->transactionDate;
    }

    public function setTransactionDate(?DateTimeInterface $transactionDate): static
    {
        $this->transactionDate = $transactionDate;

        return $this;
    }

    public function isAnonymized(): bool
    {
        return $this->anonymized;
    }

    public function setAnonymized(bool $anonymized): static
    {
        $this->anonymized = $anonymized;

        return $this;
    }
}
