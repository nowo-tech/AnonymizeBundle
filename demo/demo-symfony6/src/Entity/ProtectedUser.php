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
 * Shows two syntaxes:
 * - Multiple configs (OR between configs): exclude when ANY config matches.
 * - Array value for one field (OR within field): e.g. 'status' => ['archived', 'deleted'].
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'protected_users')]
#[Anonymize(
    excludePatterns: [
        ['email' => '%@visitor.com'],           // Config 1: exclude emails ending in @visitor.com
        ['role' => 'admin'],                    // Config 2: exclude admin users
        ['id' => '<=100'],                      // Config 3: exclude first 100 records
        ['status' => 'archived|deleted'],       // Config 4: exclude archived or deleted (| = OR within field)
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

    /**
     * This field will be set to null even if the record is excluded at entity level.
     * Uses bypass_entity_exclusion option to ensure it's processed regardless of entity exclusion.
     */
    #[AnonymizeProperty(
        type: 'null',
        weight: 5,
        options: ['bypass_entity_exclusion' => true]
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $sensitiveNotes = null;

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

    public function getSensitiveNotes(): ?string
    {
        return $this->sensitiveNotes;
    }

    public function setSensitiveNotes(?string $sensitiveNotes): static
    {
        $this->sensitiveNotes = $sensitiveNotes;

        return $this;
    }
}
