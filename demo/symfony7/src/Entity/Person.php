<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * Person entity demonstrating name_fallback faker for handling nullable related name fields.
 *
 * This entity shows how to use the name_fallback faker when you have multiple name fields
 * (e.g., 'name' and 'firstname') where one can be nullable. The faker ensures that if
 * one field has a value and the other is null, a random value is generated for the null field.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'persons')]
#[Anonymize]
class Person
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(
        type: 'name_fallback',
        weight: 1,
        options: ['fallback_field' => 'firstname', 'gender' => 'random']
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(
        type: 'name_fallback',
        weight: 2,
        options: ['fallback_field' => 'name', 'gender' => 'random']
    )]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(type: 'surname', weight: 3)]
    private ?string $surname = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(type: 'email', weight: 4)]
    private ?string $email = null;

    #[ORM\Column(nullable: true)]
    #[AnonymizeProperty(type: 'age', weight: 5, options: ['min' => 18, 'max' => 80])]
    private ?int $age = null;

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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): static
    {
        $this->surname = $surname;

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

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): static
    {
        $this->age = $age;

        return $this;
    }
}
