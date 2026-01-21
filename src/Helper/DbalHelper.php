<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Helper;

use Doctrine\DBAL\Connection;

/**
 * Helper class for Doctrine DBAL operations.
 *
 * Provides static utility methods for database operations that are compatible
 * across different DBAL versions (2.x and 3.x).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class DbalHelper
{
    /**
     * Quotes a single identifier (table or column name) for use in SQL queries.
     * Compatible with both DBAL 2.x and 3.x.
     *
     * @param Connection $connection The database connection
     * @param string $identifier The identifier to quote
     * @return string The quoted identifier
     */
    public static function quoteIdentifier(Connection $connection, string $identifier): string
    {
        // Try quoteSingleIdentifier first (DBAL 3.6+)
        if (method_exists($connection, 'quoteSingleIdentifier')) {
            return $connection->quoteSingleIdentifier($identifier);
        }

        // Fallback for older DBAL versions: use quoteIdentifier (DBAL 2.x)
        if (method_exists($connection, 'quoteIdentifier')) {
            return $connection->quoteIdentifier($identifier);
        }

        // Last resort: manual quoting with backticks (MySQL style)
        // This is a simple fallback but may not work for all databases
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
