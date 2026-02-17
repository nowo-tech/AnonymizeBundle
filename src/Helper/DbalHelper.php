<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Helper;

use Doctrine\DBAL\Connection;
use Exception;

/**
 * Helper class for Doctrine DBAL operations.
 *
 * Provides static utility methods for database operations that are compatible
 * across different DBAL versions (2.x and 3.x).
 *
 * Note on identifier quoting fallback: when neither quoteSingleIdentifier nor
 * quoteIdentifier exist on the connection, a driver-aware fallback is used
 * (MySQL: backticks, PostgreSQL/SQLite: double quotes). In DBAL-only or
 * custom setups, prefer using a DBAL version that provides proper quoting.
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
     * When DBAL does not provide quoting methods, a driver-aware fallback is used:
     * MySQL (backticks), PostgreSQL/SQLite (double quotes). Other drivers default to MySQL style.
     *
     * @param Connection $connection The database connection
     * @param string $identifier The identifier to quote
     *
     * @return string The quoted identifier
     */
    public static function quoteIdentifier(Connection $connection, string $identifier): string
    {
        // Prefer platform (mockable in tests; in DBAL 4 Connection::quoteSingleIdentifier may be final/static)
        if (method_exists($connection, 'getDatabasePlatform')) {
            try {
                $platform = $connection->getDatabasePlatform();
                if ($platform !== null && method_exists($platform, 'quoteSingleIdentifier')) {
                    return $platform->quoteSingleIdentifier($identifier);
                }
            } catch (Exception $e) {
                // Fall through to connection or fallback
            }
        }

        // Connection-level quoting (DBAL 3.x when not final)
        if (method_exists($connection, 'quoteSingleIdentifier')) {
            return $connection->quoteSingleIdentifier($identifier);
        }

        // Fallback for older DBAL versions: use quoteIdentifier (DBAL 2.x)
        if (method_exists($connection, 'quoteIdentifier')) {
            return $connection->quoteIdentifier($identifier);
        }

        // Last resort: driver-aware manual quoting (MySQL backticks, PostgreSQL/SQLite double quotes)
        return self::quoteIdentifierFallback($connection, $identifier);
    }

    /**
     * Manual identifier quoting when DBAL does not expose quoting methods.
     * Uses backticks for MySQL, double quotes for PostgreSQL and SQLite.
     */
    private static function quoteIdentifierFallback(Connection $connection, string $identifier): string
    {
        $driverName = self::getDriverName($connection);

        if ($driverName === 'pdo_pgsql' || $driverName === 'pdo_sqlite') {
            return '"' . str_replace('"', '""', $identifier) . '"';
        }

        // MySQL style (and default for unknown drivers)
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * Gets the database driver name in a compatible way across DBAL versions.
     *
     * @param Connection $connection The database connection
     *
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
            $platform     = $connection->getDatabasePlatform();
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
        } catch (Exception $e) {
            // Fall through to default
        }

        // Last resort: default to mysql
        return 'pdo_mysql';
    }
}
