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
 * When truncate=true on a polymorphic entity (Doctrine STI/CTI), only rows with
 * that entity's discriminator value are deleted; normal entities truncate the full table.
 *
 * When anonymizeService is set, the given service (implementing EntityAnonymizerServiceInterface)
 * is called for each record instead of applying AnonymizeProperty. Useful for polymorphic
 * entities or custom logic (API calls, external rules, etc.).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Anonymize
{
    /**
     * @param string|null $connection The Doctrine connection name. If null, all connections will be checked
     * @param array<array<int, array<string, array<string>|string>>|array<string>|string> $includePatterns Single config (field=>pattern) or list of configs (OR between them). If empty, all records are included
     * @param array<array<int, array<string, array<string>|string>>|array<string>|string> $excludePatterns Single config (field=>pattern) or list of configs (OR between them). E.g. ['id' => '<=100'] or [ ['role'=>'admin','email'=>'%@nowo.tech'], ['status'=>'deleted'] ]. Exclusions take precedence over inclusions
     * @param string|null $anonymizeService Service id (e.g. FQCN or container id) implementing EntityAnonymizerServiceInterface. When set, this service anonymizes each record instead of using AnonymizeProperty attributes
     * @param bool $truncate If true, the table is emptied before anonymization. For polymorphic entities (STI/CTI) only rows of this discriminator are deleted; for normal entities the whole table is truncated
     * @param int|null $truncate_order Order for truncating tables. Lower numbers are executed first. If null, tables are truncated in alphabetical order after those with explicit order
     */
    public function __construct(
        public ?string $connection = null,
        public array $includePatterns = [],
        public array $excludePatterns = [],
        public ?string $anonymizeService = null,
        public bool $truncate = false,
        public ?int $truncate_order = null
    ) {
    }
}
