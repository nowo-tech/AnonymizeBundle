<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Enum\FakerType;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * FakerTypeExample entity demonstrating the use of FakerType enum.
 *
 * This entity shows how to use FakerType enum instead of strings
 * for better type safety and IDE autocompletion.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'faker_type_examples')]
#[Anonymize]
class FakerTypeExample
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Example using FakerType enum (recommended approach).
     */
    #[AnonymizeProperty(type: FakerType::EMAIL, weight: 1)]
    #[ORM\Column(type: Types::STRING, length: 180)]
    private ?string $email = null;

    /**
     * Example using FakerType enum with options.
     */
    #[AnonymizeProperty(
        type: FakerType::DNI_CIF,
        weight: 2,
        options: [
            'type' => 'dni',
            'preserve_null' => true,
        ]
    )]
    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    private ?string $legalId = null;

    /**
     * Example using FakerType enum for HTML content.
     */
    #[AnonymizeProperty(
        type: FakerType::HTML,
        weight: 3,
        options: [
            'type' => 'signature',
            'include_links' => true,
        ]
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $signature = null;

    /**
     * Example using FakerType enum for pattern-based faker.
     */
    #[AnonymizeProperty(
        type: FakerType::PATTERN_BASED,
        weight: 4,
        options: [
            'source_field' => 'email',
            'pattern' => '/(\\(\\d+\\))$/',
            'pattern_replacement' => '$1',
        ]
    )]
    #[ORM\Column(type: Types::STRING, length: 180)]
    private ?string $username = null;

    /**
     * Example using FakerType enum for null faker with bypass.
     */
    #[AnonymizeProperty(
        type: FakerType::NULL,
        weight: 5,
        options: ['bypass_entity_exclusion' => true]
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $sensitiveNotes = null;

    /**
     * Example showing that string still works (backward compatibility).
     */
    #[AnonymizeProperty(type: 'name', weight: 6)]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

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

    public function getLegalId(): ?string
    {
        return $this->legalId;
    }

    public function setLegalId(?string $legalId): static
    {
        $this->legalId = $legalId;

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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
