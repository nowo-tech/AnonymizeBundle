<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Attribute;

use Attribute;

/**
 * Attribute to mark an entity class for anonymization.
 *
 * This attribute can be placed on an entity class to indicate that it should be
 * processed during anonymization. It allows specifying connection name and
 * inclusion/exclusion patterns.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Anonymize
{
    /**
     * @param string|null $connection The Doctrine connection name. If null, all connections will be checked
     * @param array<string> $includePatterns Array of patterns to include (e.g., ['id' => '>100']). If empty, all records are included
     * @param array<string> $excludePatterns Array of patterns to exclude (e.g., ['id' => '<=100']). Exclusions take precedence over inclusions
     */
    public function __construct(
        public ?string $connection = null,
        public array $includePatterns = [],
        public array $excludePatterns = []
    ) {}
}
