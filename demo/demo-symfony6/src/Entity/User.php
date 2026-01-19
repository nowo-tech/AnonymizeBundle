<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

/**
 * User entity for demo purposes.
 *
 * This entity demonstrates how to use the AnonymizeBundle attributes.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[Anonymize]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'email', weight: 1)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'name', weight: 2)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'surname', weight: 3)]
    private ?string $lastName = null;

    #[ORM\Column]
    #[AnonymizeProperty(type: 'age', weight: 4, options: ['min' => 18, 'max' => 100])]
    private ?int $age = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[AnonymizeProperty(type: 'phone', weight: 5)]
    private ?string $phone = null;

    #[ORM\Column(length: 34, nullable: true)]
    #[AnonymizeProperty(type: 'iban', weight: 6, options: ['country' => 'ES'])]
    private ?string $iban = null;

    #[ORM\Column(length: 19, nullable: true)]
    #[AnonymizeProperty(type: 'credit_card', weight: 7)]
    private ?string $creditCard = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): static
    {
        $this->age = $age;

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
