<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Helper;

use function array_key_exists;
use function array_keys;
use function strcasecmp;

/**
 * Finds a column in a fetched row (since 1.5, audits LQ-N-D-L1 and LQ-N-D2-L1).
 *
 * PostgreSQL folds unquoted identifiers to lower case (Oracle to upper case), so the row of a camelCase column
 * `fullName` carries the key `fullname`. The exact key wins; otherwise the one key that is equal ignoring letter case.
 * Two such keys are ambiguous and give `null`, like a missing column.
 *
 * @internal
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class RowKey
{
    /**
     * @param array<string, mixed> $row A fetched row (column name => value)
     *
     * @return string|null The row's own key for the column, or null when the row has no (or no unique) such key
     */
    public static function resolve(array $row, string $column): ?string
    {
        if (array_key_exists($column, $row)) {
            return $column;
        }

        $match = null;
        foreach (array_keys($row) as $key) {
            if (strcasecmp($key, $column) === 0) {
                if ($match !== null) {
                    return null;
                }
                $match = $key;
            }
        }

        return $match;
    }
}
