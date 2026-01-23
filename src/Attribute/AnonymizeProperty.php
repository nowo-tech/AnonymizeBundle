<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Attribute;

use Attribute;
use Nowo\AnonymizeBundle\Enum\FakerType;

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
     * @param FakerType|string $type The faker type. Can be a FakerType enum (recommended) or a string.
     *   Examples:
     *   - Using enum: type: FakerType::DNI_CIF
     *   - Using string: type: 'dni_cif'
     *   Available types: email, name, surname, age, phone, iban, credit_card, address, date, username, url, company,
     *   masking, password, ip_address, mac_address, uuid, hash, coordinate, color, boolean, numeric, file, json, text,
     *   enum, country, language, hash_preserve, shuffle, constant, service, dni_cif, name_fallback, html, pattern_based,
     *   copy, null
     * @param int|null $weight The weight for ordering anonymization. Lower weights are processed first. If null, processed last alphabetically
     * @param array<string> $includePatterns Array of patterns to include (e.g., ['id' => '>100']). If empty, all records are included
     * @param array<string> $excludePatterns Array of patterns to exclude (e.g., ['id' => '<=100']). Exclusions take precedence over inclusions
     * @param string|null $service If type is 'service', the service name to use for anonymization
     * @param array<string, mixed> $options Additional options for the faker (e.g., ['min' => 18, 'max' => 65] for age)
     */
    public function __construct(
        FakerType|string $type,
        public ?int $weight = null,
        public array $includePatterns = [],
        public array $excludePatterns = [],
        public ?string $service = null,
        public array $options = []
    ) {
        // Convert enum to string for internal storage and backward compatibility
        $this->type = $type instanceof FakerType ? $type->value : $type;
    }

    /**
     * The faker type (always stored as string for backward compatibility).
     */
    public string $type;
}
