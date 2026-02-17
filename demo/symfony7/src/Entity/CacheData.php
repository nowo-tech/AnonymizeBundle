<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Enum\FakerType;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * CacheData entity demonstrating truncate with order.
 *
 * This entity shows how to use truncate with a specific order.
 * If you have multiple tables with dependencies, you can control
 * the truncation order using truncate_order.
 *
 * Example scenario:
 * - TempData has truncate_order: 1 (truncated first)
 * - CacheData has truncate_order: 2 (truncated second)
 * - If CacheData depends on TempData, this ensures correct order
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'cache_data')]
#[Anonymize(
    truncate: true,  // This table will be emptied before anonymization
    truncate_order: 2,  // Execute truncation after TempData (order 1)
)]
class CacheData
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[AnonymizeProperty(type: FakerType::TEXT, weight: 1, options: ['type' => 'sentence', 'min_words' => 5, 'max_words' => 10])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $cacheKey = null;

    #[AnonymizeProperty(type: FakerType::JSON, weight: 2, options: ['max_depth' => 2, 'max_items' => 5])]
    #[ORM\Column(type: Types::JSON)]
    private array $cacheValue = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $expiresAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCacheKey(): ?string
    {
        return $this->cacheKey;
    }

    public function setCacheKey(?string $cacheKey): static
    {
        $this->cacheKey = $cacheKey;

        return $this;
    }

    public function getCacheValue(): array
    {
        return $this->cacheValue;
    }

    public function setCacheValue(array $cacheValue): static
    {
        $this->cacheValue = $cacheValue;

        return $this;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }
}
