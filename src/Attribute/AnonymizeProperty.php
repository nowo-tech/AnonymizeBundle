<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Attribute;

use Attribute;

/**
 * Attribute to mark a property for anonymization.
 *
 * This attribute is placed on entity properties to specify how they should be anonymized.
 * It supports different faker types, weights for ordering, and inclusion/exclusion patterns.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class AnonymizeProperty
{
    /**
     * @param string $type The faker type: 'email', 'name', 'surname', 'age', 'phone', 'iban', 'credit_card', or 'service'
     * @param int|null $weight The weight for ordering anonymization. Lower weights are processed first. If null, processed last alphabetically
     * @param array<string> $includePatterns Array of patterns to include (e.g., ['id' => '>100']). If empty, all records are included
     * @param array<string> $excludePatterns Array of patterns to exclude (e.g., ['id' => '<=100']). Exclusions take precedence over inclusions
     * @param string|null $service If type is 'service', the service name to use for anonymization
     * @param array<string, mixed> $options Additional options for the faker (e.g., ['min' => 18, 'max' => 65] for age)
     */
    public function __construct(
        public string $type,
        public ?int $weight = null,
        public array $includePatterns = [],
        public array $excludePatterns = [],
        public ?string $service = null,
        public array $options = []
    ) {
    }
}
