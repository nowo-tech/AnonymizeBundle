<?php

declare(strict_types=1);

namespace App\Document;

use DateTimeInterface;

/**
 * AnalyticsEvent document for MongoDB.
 *
 * Demonstrates structured data fakers: json, text, enum, country, language, hash_preserve, shuffle, constant.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnalyticsEvent
{
    private ?string $id                   = null;
    private ?string $eventId              = null;
    private ?string $eventType            = null; // enum: 'page_view', 'click', 'purchase', 'signup'
    private ?string $country              = null;
    private ?string $language             = null;
    private ?string $eventData            = null; // JSON
    private ?string $description          = null; // Text
    private ?string $userIdHash           = null; // hash_preserve
    private ?string $category             = null; // shuffle
    private ?string $dataClassification   = null; // constant
    private ?DateTimeInterface $timestamp = null;
    private bool $anonymized              = false;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getEventId(): ?string
    {
        return $this->eventId;
    }

    public function setEventId(?string $eventId): static
    {
        $this->eventId = $eventId;

        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(?string $eventType): static
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getEventData(): ?string
    {
        return $this->eventData;
    }

    public function setEventData(?string $eventData): static
    {
        $this->eventData = $eventData;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUserIdHash(): ?string
    {
        return $this->userIdHash;
    }

    public function setUserIdHash(?string $userIdHash): static
    {
        $this->userIdHash = $userIdHash;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getDataClassification(): ?string
    {
        return $this->dataClassification;
    }

    public function setDataClassification(?string $dataClassification): static
    {
        $this->dataClassification = $dataClassification;

        return $this;
    }

    public function getTimestamp(): ?DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(?DateTimeInterface $timestamp): static
    {
        $this->timestamp = $timestamp;

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
