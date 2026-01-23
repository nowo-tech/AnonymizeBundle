<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * ProtectedUser entity demonstrating excludePatterns at entity level.
 *
 * This entity shows how to exclude entire records from anonymization
 * when certain fields match specific patterns.
 *
 * Examples:
 * - Exclude users with email ending in @visitor.com
 * - Exclude users with role = 'admin'
 * - Exclude users with id <= 100
 * - Exclude users with status = 'archived|deleted'
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'protected_users')]
#[Anonymize(
    excludePatterns: [
        'email' => '%@visitor.com',        // Exclude emails ending in @visitor.com
        'role' => 'admin',                 // Exclude admin users
        'id' => '<=100',                   // Exclude first 100 records
        'status' => 'archived|deleted',    // Exclude archived or deleted users
    ]
)]
class ProtectedUser
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[AnonymizeProperty(type: 'email', weight: 1)]
    #[ORM\Column(type: Types::STRING, length: 180)]
    private ?string $email = null;

    #[AnonymizeProperty(type: 'name', weight: 2)]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[AnonymizeProperty(type: 'phone', weight: 3)]
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $role = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $status = null;

    #[AnonymizeProperty(type: 'address', weight: 4)]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $address = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
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

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }
}
