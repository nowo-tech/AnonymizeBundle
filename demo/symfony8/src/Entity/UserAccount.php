<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * UserAccount entity demonstrating copy and pattern_based fakers.
 *
 * This entity shows how to use:
 * - `copy` faker to copy values from other fields (e.g., emailCanonical from email)
 * - `pattern_based` faker to construct values from other fields while preserving patterns
 *   (e.g., username from email with number suffix like email@domain.com(15))
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'user_accounts')]
#[Anonymize]
class UserAccount
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[AnonymizeProperty(type: 'email', weight: 1, excludePatterns: [
        'email' => ['%@visitor.com', '%@internal.com'],  // Array value: exclude when email matches any option
    ])]
    #[Assert\Email(message: 'validator.email.not_valid')]
    #[ORM\Column(type: Types::STRING, length: 180)]
    public string $email;

    #[AnonymizeProperty(
        type: 'pattern_based',
        weight: 2,
        options: [
            'source_field'        => 'email',  // Use anonymized email as base
            'pattern'             => '/(\\(\\d+\\))$/',  // Extract (number) at the end
            'pattern_replacement' => '$1',  // Keep the extracted pattern
        ],
    )]
    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    protected string $username;

    #[AnonymizeProperty(
        type: 'pattern_based',
        weight: 3,
        options: [
            'source_field'        => 'email',
            'pattern'             => '/(\\(\\d+\\))$/',
            'pattern_replacement' => '$1',
        ],
    )]
    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    protected string $usernameCanonical;

    #[AnonymizeProperty(
        type: 'copy',
        weight: 4,
        options: [
            'source_field' => 'email',  // Copy from anonymized email (same value)
        ],
    )]
    #[ORM\Column(type: Types::STRING, length: 180)]
    protected string $emailCanonical;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getUsernameCanonical(): string
    {
        return $this->usernameCanonical;
    }

    public function setUsernameCanonical(string $usernameCanonical): static
    {
        $this->usernameCanonical = $usernameCanonical;

        return $this;
    }

    public function getEmailCanonical(): string
    {
        return $this->emailCanonical;
    }

    public function setEmailCanonical(string $emailCanonical): static
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }
}
