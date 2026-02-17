<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * SystemLog entity demonstrating all remaining faker types.
 *
 * This entity showcases: password, ip_address, mac_address, uuid, hash,
 * coordinate, color, boolean, numeric, file, json, text, enum, country, language,
 * hash_preserve, shuffle, constant.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'system_logs')]
#[Anonymize]
class SystemLog
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36)]
    #[AnonymizeProperty(type: 'uuid', weight: 1, options: ['version' => 4])]
    private ?string $sessionId = null;

    #[ORM\Column(length: 45)]
    #[AnonymizeProperty(type: 'ip_address', weight: 2, options: ['type' => 'ipv4', 'public' => true])]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 17)]
    #[AnonymizeProperty(type: 'mac_address', weight: 3, options: ['separator' => ':', 'uppercase' => false])]
    private ?string $macAddress = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'password', weight: 4, options: ['length' => 16, 'special_chars' => true, 'numbers' => true, 'uppercase' => true])]
    private ?string $apiKey = null;

    #[ORM\Column(length: 64)]
    #[AnonymizeProperty(type: 'hash', weight: 5, options: ['algorithm' => 'sha256'])]
    private ?string $tokenHash = null;

    #[ORM\Column(length: 50)]
    #[AnonymizeProperty(type: 'coordinate', weight: 6, options: ['format' => 'decimal', 'precision' => 6])]
    private ?string $location = null;

    #[ORM\Column(length: 7)]
    #[AnonymizeProperty(type: 'color', weight: 7, options: ['format' => 'hex', 'alpha' => false])]
    private ?string $themeColor = null;

    #[ORM\Column(type: 'boolean')]
    #[AnonymizeProperty(type: 'boolean', weight: 8, options: ['true_probability' => 0.7])]
    private ?bool $isActive = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[AnonymizeProperty(type: 'numeric', weight: 9, options: ['type' => 'float', 'min' => 0, 'max' => 1000, 'precision' => 2])]
    private ?string $score = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'file', weight: 10, options: ['extension' => 'log', 'directory' => 'logs', 'absolute' => false])]
    private ?string $logFile = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[AnonymizeProperty(type: 'json', weight: 11, options: ['depth' => 3, 'max_items' => 5])]
    private ?string $metadata = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[AnonymizeProperty(type: 'text', weight: 12, options: ['type' => 'paragraph', 'min_words' => 10, 'max_words' => 50])]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[AnonymizeProperty(type: 'enum', weight: 13, options: ['values' => ['info', 'warning', 'error', 'debug'], 'weighted' => ['info' => 50, 'warning' => 30, 'error' => 15, 'debug' => 5]])]
    private ?string $logLevel = null;

    #[ORM\Column(length: 2)]
    #[AnonymizeProperty(type: 'country', weight: 14, options: ['format' => 'code'])]
    private ?string $countryCode = null;

    #[ORM\Column(length: 5)]
    #[AnonymizeProperty(type: 'language', weight: 15, options: ['format' => 'code'])]
    private ?string $languageCode = null;

    #[ORM\Column(type: 'datetime')]
    #[AnonymizeProperty(type: 'date', weight: 16, options: ['type' => 'past', 'min_date' => '-1 year', 'max_date' => 'now', 'format' => 'Y-m-d H:i:s'])]
    private ?DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[AnonymizeProperty(type: 'hash_preserve', weight: 17, options: ['algorithm' => 'sha256', 'salt' => 'demo-salt'])]
    private ?string $userIdHash = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[AnonymizeProperty(type: 'shuffle', weight: 18, options: ['values' => ['pending', 'processing', 'completed', 'failed']])]
    private ?string $processStatus = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[AnonymizeProperty(type: 'constant', weight: 19, options: ['value' => 'ANONYMIZED'])]
    private ?string $dataClassification = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): static
    {
        $this->sessionId = $sessionId;

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

    public function getMacAddress(): ?string
    {
        return $this->macAddress;
    }

    public function setMacAddress(?string $macAddress): static
    {
        $this->macAddress = $macAddress;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getTokenHash(): ?string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(?string $tokenHash): static
    {
        $this->tokenHash = $tokenHash;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getThemeColor(): ?string
    {
        return $this->themeColor;
    }

    public function setThemeColor(?string $themeColor): static
    {
        $this->themeColor = $themeColor;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getScore(): ?string
    {
        return $this->score;
    }

    public function setScore(?string $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    public function setLogFile(?string $logFile): static
    {
        $this->logFile = $logFile;

        return $this;
    }

    public function getMetadata(): ?string
    {
        return $this->metadata;
    }

    public function setMetadata(?string $metadata): static
    {
        $this->metadata = $metadata;

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

    public function getLogLevel(): ?string
    {
        return $this->logLevel;
    }

    public function setLogLevel(?string $logLevel): static
    {
        $this->logLevel = $logLevel;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): static
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getLanguageCode(): ?string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(?string $languageCode): static
    {
        $this->languageCode = $languageCode;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

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

    public function getProcessStatus(): ?string
    {
        return $this->processStatus;
    }

    public function setProcessStatus(?string $processStatus): static
    {
        $this->processStatus = $processStatus;

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
}
