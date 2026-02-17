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
 * TempData entity demonstrating the use of truncate option.
 *
 * This entity shows how to use the truncate option to empty a table
 * before anonymization. This is useful for temporary data tables,
 * cache tables, or tables that should be completely cleared.
 *
 * The truncate_order option allows you to control the order in which
 * tables are truncated, which is important when dealing with foreign
 * key dependencies.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'temp_data')]
#[Anonymize(
    truncate: true,  // This table will be emptied before anonymization
    truncate_order: 1,  // Execute truncation first (lower numbers = earlier)
)]
class TempData
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[AnonymizeProperty(type: FakerType::EMAIL, weight: 1)]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $email = null;

    #[AnonymizeProperty(type: FakerType::NAME, weight: 2)]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[AnonymizeProperty(type: FakerType::PHONE, weight: 3)]
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?DateTimeImmutable $createdAt = null;

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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
