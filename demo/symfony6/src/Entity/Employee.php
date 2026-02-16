<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * Employee entity demonstrating username and various data types.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'employees')]
#[Anonymize(
    excludePatterns: ['department' => 'HR']
)]
class Employee
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'name', weight: 1)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'surname', weight: 2)]
    private ?string $lastName = null;

    #[ORM\Column(length: 100, unique: true)]
    #[AnonymizeProperty(type: 'username', weight: 3, options: ['min_length' => 6, 'max_length' => 15, 'include_numbers' => true])]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'email', weight: 4)]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[AnonymizeProperty(type: 'phone', weight: 5)]
    private ?string $phone = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[AnonymizeProperty(type: 'date', weight: 6, options: ['type' => 'past', 'min_date' => '-65 years', 'max_date' => '-18 years', 'format' => 'Y-m-d'])]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column]
    #[AnonymizeProperty(type: 'age', weight: 7, options: ['min' => 22, 'max' => 65])]
    private ?int $age = null;

    #[ORM\Column(length: 100)]
    private ?string $department = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[AnonymizeProperty(type: 'company', weight: 8, options: ['type' => 'llc'])]
    private ?string $previousCompany = null;

    #[ORM\Column(type: 'datetime')]
    #[AnonymizeProperty(type: 'date', weight: 9, options: ['type' => 'past', 'min_date' => '-10 years', 'max_date' => 'now', 'format' => 'Y-m-d H:i:s'])]
    private ?\DateTimeInterface $hireDate = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

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

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;

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

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): static
    {
        $this->department = $department;

        return $this;
    }

    public function getPreviousCompany(): ?string
    {
        return $this->previousCompany;
    }

    public function setPreviousCompany(?string $previousCompany): static
    {
        $this->previousCompany = $previousCompany;

        return $this;
    }

    public function getHireDate(): ?\DateTimeInterface
    {
        return $this->hireDate;
    }

    public function setHireDate(?\DateTimeInterface $hireDate): static
    {
        $this->hireDate = $hireDate;

        return $this;
    }
}
