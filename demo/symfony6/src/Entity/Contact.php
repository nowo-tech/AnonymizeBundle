<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * Contact entity demonstrating nullable and preserve_null options.
 *
 * This entity shows how to use:
 * - `nullable` and `null_probability` options to generate null values with configurable probability
 * - `preserve_null` option to skip anonymization when original value is null
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'contacts')]
#[Anonymize]
class Contact
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'name', weight: 1)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(
        type: 'email',
        weight: 2,
        options: [
            'nullable' => true,
            'null_probability' => 20,  // 20% chance of being null
        ]
    )]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[AnonymizeProperty(
        type: 'phone',
        weight: 3,
        options: [
            'preserve_null' => true,  // Only anonymize if phone has a value
        ]
    )]
    private ?string $phone = null;

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    #[AnonymizeProperty(
        type: 'dni_cif',
        weight: 4,
        options: [
            'type' => 'dni',
            'preserve_null' => true,  // Only anonymize if legalId has a value
        ]
    )]
    private ?string $legalId = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(
        type: 'address',
        weight: 5,
        options: [
            'nullable' => true,
            'null_probability' => 30,  // 30% chance of being null
        ]
    )]
    private ?string $address = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[AnonymizeProperty(
        type: 'html',
        weight: 6,
        options: [
            'type' => 'signature',
            'include_links' => true,
            'preserve_null' => true,  // Only anonymize if signature has a value
        ]
    )]
    private ?string $emailSignature = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

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

    public function getLegalId(): ?string
    {
        return $this->legalId;
    }

    public function setLegalId(?string $legalId): static
    {
        $this->legalId = $legalId;

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

    public function getEmailSignature(): ?string
    {
        return $this->emailSignature;
    }

    public function setEmailSignature(?string $emailSignature): static
    {
        $this->emailSignature = $emailSignature;

        return $this;
    }
}
