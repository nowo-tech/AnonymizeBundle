<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * CustomFakerExample entity demonstrating the use of ExampleCustomFaker.
 *
 * This entity showcases how to use a custom faker service that:
 * - Preserves the original value (for testing events)
 * - Accesses other fields from the current record
 * - Can access related entities using EntityManager
 * - Demonstrates event handling capabilities
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'custom_faker_examples')]
#[Anonymize]
class CustomFakerExample
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * This field uses ExampleCustomFaker with preserve_original=true.
     * This means the value will NOT be anonymized, allowing you to see
     * how events work and what data is available.
     */
    #[AnonymizeProperty(
        type: 'service',
        service: 'Nowo\AnonymizeBundle\Faker\Example\ExampleCustomFaker',
        weight: 1,
        options: [
            'preserve_original' => true,  // Set to false to actually anonymize
        ]
    )]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $preservedField = null;

    /**
     * This field uses ExampleCustomFaker with preserve_original=false.
     * This will actually anonymize the value using the custom logic.
     */
    #[AnonymizeProperty(
        type: 'service',
        service: 'Nowo\AnonymizeBundle\Faker\Example\ExampleCustomFaker',
        weight: 2,
        options: [
            'preserve_original' => false,  // This will anonymize
        ]
    )]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $anonymizedField = null;

    /**
     * This field demonstrates accessing other fields from the record.
     * The ExampleCustomFaker can access this field via $options['record']['referenceField'].
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $referenceField = null;

    /**
     * This field uses ExampleCustomFaker and shows how to pass custom options.
     * The faker can access these options via $options['custom_option'].
     */
    #[AnonymizeProperty(
        type: 'service',
        service: 'Nowo\AnonymizeBundle\Faker\Example\ExampleCustomFaker',
        weight: 3,
        options: [
            'preserve_original' => false,
            'custom_option' => 'example_value',
        ]
    )]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $customOptionField = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPreservedField(): ?string
    {
        return $this->preservedField;
    }

    public function setPreservedField(?string $preservedField): static
    {
        $this->preservedField = $preservedField;

        return $this;
    }

    public function getAnonymizedField(): ?string
    {
        return $this->anonymizedField;
    }

    public function setAnonymizedField(?string $anonymizedField): static
    {
        $this->anonymizedField = $anonymizedField;

        return $this;
    }

    public function getReferenceField(): ?string
    {
        return $this->referenceField;
    }

    public function setReferenceField(?string $referenceField): static
    {
        $this->referenceField = $referenceField;

        return $this;
    }

    public function getCustomOptionField(): ?string
    {
        return $this->customOptionField;
    }

    public function setCustomOptionField(?string $customOptionField): static
    {
        $this->customOptionField = $customOptionField;

        return $this;
    }
}
