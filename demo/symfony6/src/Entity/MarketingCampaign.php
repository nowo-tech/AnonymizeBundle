<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Enum\FakerType;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * MarketingCampaign entity demonstrating the use of UtmFaker.
 *
 * This entity showcases how to use UtmFaker for different UTM parameter types:
 * - utm_source: Traffic source (e.g., google, facebook, newsletter)
 * - utm_medium: Marketing medium (e.g., cpc, email, social)
 * - utm_campaign: Campaign name (e.g., spring_sale, product_launch)
 * - utm_term: Search term (for paid search campaigns)
 * - utm_content: Content identifier (to differentiate links)
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'marketing_campaigns')]
#[Anonymize]
class MarketingCampaign
{
    use AnonymizableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * UTM source parameter - the source of traffic.
     */
    #[AnonymizeProperty(
        type: FakerType::UTM,
        weight: 1,
        options: [
            'type'   => 'source',
            'format' => 'snake_case',
        ],
    )]
    #[ORM\Column(type: Types::STRING, length: 100)]
    private ?string $utmSource = null;

    /**
     * UTM medium parameter - the marketing medium.
     */
    #[AnonymizeProperty(
        type: FakerType::UTM,
        weight: 2,
        options: [
            'type'   => 'medium',
            'format' => 'snake_case',
        ],
    )]
    #[ORM\Column(type: Types::STRING, length: 100)]
    private ?string $utmMedium = null;

    /**
     * UTM campaign parameter - the campaign name.
     */
    #[AnonymizeProperty(
        type: FakerType::UTM,
        weight: 3,
        options: [
            'type'       => 'campaign',
            'format'     => 'snake_case',
            'min_length' => 5,
            'max_length' => 30,
        ],
    )]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $utmCampaign = null;

    /**
     * UTM term parameter - the search term (for paid search).
     */
    #[AnonymizeProperty(
        type: FakerType::UTM,
        weight: 4,
        options: [
            'type'       => 'term',
            'format'     => 'snake_case',
            'min_length' => 3,
            'max_length' => 20,
        ],
    )]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $utmTerm = null;

    /**
     * UTM content parameter - content identifier.
     */
    #[AnonymizeProperty(
        type: FakerType::UTM,
        weight: 5,
        options: [
            'type'       => 'content',
            'format'     => 'snake_case',
            'min_length' => 5,
            'max_length' => 25,
        ],
    )]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $utmContent = null;

    /**
     * Example with kebab-case format.
     */
    #[AnonymizeProperty(
        type: FakerType::UTM,
        weight: 6,
        options: [
            'type'   => 'source',
            'format' => 'kebab-case',
        ],
    )]
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $utmSourceKebab = null;

    /**
     * Example with custom sources.
     */
    #[AnonymizeProperty(
        type: FakerType::UTM,
        weight: 7,
        options: [
            'type'           => 'source',
            'custom_sources' => ['partner_a', 'partner_b', 'partner_c'],
        ],
    )]
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $utmSourceCustom = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtmSource(): ?string
    {
        return $this->utmSource;
    }

    public function setUtmSource(?string $utmSource): static
    {
        $this->utmSource = $utmSource;

        return $this;
    }

    public function getUtmMedium(): ?string
    {
        return $this->utmMedium;
    }

    public function setUtmMedium(?string $utmMedium): static
    {
        $this->utmMedium = $utmMedium;

        return $this;
    }

    public function getUtmCampaign(): ?string
    {
        return $this->utmCampaign;
    }

    public function setUtmCampaign(?string $utmCampaign): static
    {
        $this->utmCampaign = $utmCampaign;

        return $this;
    }

    public function getUtmTerm(): ?string
    {
        return $this->utmTerm;
    }

    public function setUtmTerm(?string $utmTerm): static
    {
        $this->utmTerm = $utmTerm;

        return $this;
    }

    public function getUtmContent(): ?string
    {
        return $this->utmContent;
    }

    public function setUtmContent(?string $utmContent): static
    {
        $this->utmContent = $utmContent;

        return $this;
    }

    public function getUtmSourceKebab(): ?string
    {
        return $this->utmSourceKebab;
    }

    public function setUtmSourceKebab(?string $utmSourceKebab): static
    {
        $this->utmSourceKebab = $utmSourceKebab;

        return $this;
    }

    public function getUtmSourceCustom(): ?string
    {
        return $this->utmSourceCustom;
    }

    public function setUtmSourceCustom(?string $utmSourceCustom): static
    {
        $this->utmSourceCustom = $utmSourceCustom;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
