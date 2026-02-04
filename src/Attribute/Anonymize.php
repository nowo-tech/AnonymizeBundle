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
     * @param array<string|array<string>|array<int, array<string, string|array<string>>>> $includePatterns Single config (field=>pattern) or list of configs (OR between them). If empty, all records are included
     * @param array<string|array<string>|array<int, array<string, string|array<string>>>> $excludePatterns Single config (field=>pattern) or list of configs (OR between them). E.g. ['id' => '<=100'] or [ ['role'=>'admin','email'=>'%@nowo.tech'], ['status'=>'deleted'] ]. Exclusions take precedence over inclusions
     * @param bool $truncate If true, the table will be truncated (emptied) before anonymization. This is executed first, before any anonymization
     * @param int|null $truncate_order Order for truncating tables. Lower numbers are executed first. If null, tables are truncated in alphabetical order after those with explicit order
     */
    public function __construct(
        public ?string $connection = null,
        public array $includePatterns = [],
        public array $excludePatterns = [],
        public bool $truncate = false,
        public ?int $truncate_order = null
    ) {}
}
