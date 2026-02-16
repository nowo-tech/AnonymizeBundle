<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\EmailNotification;
use App\Entity\SmsNotification;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * Base entity for polymorphic notifications (Doctrine Single Table Inheritance).
 *
 * Subclasses EmailNotification and SmsNotification share the same table `notifications`
 * with a discriminator column `type`. When truncate=true on a subclass, only rows
 * with that discriminator value are deleted (e.g. DELETE WHERE type = 'email').
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'notifications')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', length: 20)]
#[ORM\DiscriminatorMap([
    'email' => EmailNotification::class, 
    'sms' => SmsNotification::class
])]
abstract class AbstractNotification
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
