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

    /**
     * Gets the database driver name in a compatible way across DBAL versions.
     *
     * @param Connection $connection The database connection
     * @return string The driver name (e.g., 'pdo_mysql', 'pdo_pgsql', 'pdo_sqlite')
     */
    public static function getDriverName(Connection $connection): string
    {
        $driver = $connection->getDriver();
        
        // Try getName() method (DBAL 2.x)
        if (method_exists($driver, 'getName')) {
            return $driver->getName();
        }
        
        // Try getDatabasePlatform() and get name from platform (DBAL 3.x)
        try {
            $platform = $connection->getDatabasePlatform();
            $platformName = $platform::class;
            
            // Extract driver name from platform class name
            if (str_contains($platformName, 'MySQL')) {
                return 'pdo_mysql';
            }
            if (str_contains($platformName, 'PostgreSQL')) {
                return 'pdo_pgsql';
            }
            if (str_contains($platformName, 'Sqlite')) {
                return 'pdo_sqlite';
            }
            
            // Fallback: try to get from connection params
            $params = $connection->getParams();
            if (isset($params['driver'])) {
                return $params['driver'];
            }
            if (isset($params['driverClass'])) {
                $driverClass = $params['driverClass'];
                if (str_contains($driverClass, 'MySQL')) {
                    return 'pdo_mysql';
                }
                if (str_contains($driverClass, 'PostgreSQL')) {
                    return 'pdo_pgsql';
                }
                if (str_contains($driverClass, 'Sqlite')) {
                    return 'pdo_sqlite';
                }
            }
        } catch (\Exception $e) {
            // Fall through to default
        }
        
        // Last resort: default to mysql
        return 'pdo_mysql';
    }
}
