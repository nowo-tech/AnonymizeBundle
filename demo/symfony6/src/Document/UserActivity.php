<?php

declare(strict_types=1);

namespace App\Document;

/**
 * UserActivity document for MongoDB.
 *
 * This document is prepared for when the AnonymizeBundle supports MongoDB ODM.
 * Currently, MongoDB infrastructure is ready but the bundle only supports Doctrine ORM.
 *
 * To use this document when MongoDB support is added:
 * 1. Install doctrine/mongodb-odm-bundle
 * 2. Configure Doctrine ODM in config/packages/doctrine_mongodb.yaml
 * 3. Uncomment the annotations/attributes below
 * 4. Update the bundle to support MongoDB ODM
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */

// TODO: Uncomment when MongoDB ODM support is added to the bundle
/*
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

#[MongoDB\Document(collection: 'user_activities')]
#[Anonymize]
class UserActivity
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[AnonymizeProperty(type: 'email', weight: 1)]
    private ?string $userEmail = null;

    #[MongoDB\Field(type: 'string')]
    #[AnonymizeProperty(type: 'name', weight: 2)]
    private ?string $userName = null;

    #[MongoDB\Field(type: 'string')]
    #[AnonymizeProperty(type: 'ip', weight: 3)]
    private ?string $ipAddress = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $action = null;

    #[MongoDB\Field(type: 'date')]
    #[AnonymizeProperty(type: 'date', weight: 4, options: ['type' => 'past', 'min_date' => '-1 year', 'max_date' => 'now'])]
    private ?\DateTimeInterface $timestamp = null;

    #[MongoDB\Field(type: 'hash')]
    #[AnonymizeProperty(type: 'name', weight: 5)]
    private array $metadata = [];

    #[MongoDB\Field(type: 'bool')]
    private bool $anonymized = false;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    public function setUserEmail(?string $userEmail): static
    {
        $this->userEmail = $userEmail;
        return $this;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(?string $userName): static
    {
        $this->userName = $userName;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(?\DateTimeInterface $timestamp): static
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function isAnonymized(): bool
    {
        return $this->anonymized;
    }

    public function setAnonymized(bool $anonymized): static
    {
        $this->anonymized = $anonymized;
        return $this;
    }
}
*/

// Placeholder class to prevent autoloader errors
class UserActivity
{
    // This class will be replaced when MongoDB ODM support is added
}
