<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * Order entity demonstrating address and date anonymization.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders')]
#[Anonymize(
    // Example: Anonymize orders where type.name contains 'HR' (e.g., 'HR%', '%HR%', '%HR')
    includePatterns: ['type.name' => '%HR', 'status' => 'completed'],
    excludePatterns: ['id' => '<=5'],
)]
class Order
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[AnonymizeProperty(
        type: 'service',
        service: 'App\Service\CustomReferenceFaker',
        weight: 1,
        options: ['prefix' => 'ORD', 'length' => 10, 'separator' => '-'],
    )]
    private ?string $orderNumber = null;

    #[ORM\Column(length: 255)]
    #[AnonymizeProperty(type: 'address', weight: 2, options: ['format' => 'full', 'include_postal_code' => true])]
    private ?string $shippingAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(type: 'address', weight: 3, options: ['format' => 'full', 'include_postal_code' => true])]
    private ?string $billingAddress = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[AnonymizeProperty(type: 'age', weight: 4, options: ['min' => 50, 'max' => 5000])]
    private ?string $totalAmount = null;

    #[ORM\Column(type: 'datetime')]
    #[AnonymizeProperty(type: 'date', weight: 5, options: ['type' => 'past', 'min_date' => '-1 year', 'max_date' => 'now', 'format' => 'Y-m-d H:i:s'])]
    private ?DateTimeInterface $orderDate = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[AnonymizeProperty(type: 'email', weight: 6)]
    private ?string $customerEmail = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Type $type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(?string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?string $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?string $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getOrderDate(): ?DateTimeInterface
    {
        return $this->orderDate;
    }

    public function setOrderDate(?DateTimeInterface $orderDate): static
    {
        $this->orderDate = $orderDate;

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

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(?string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function setType(?Type $type): static
    {
        $this->type = $type;

        return $this;
    }
}
