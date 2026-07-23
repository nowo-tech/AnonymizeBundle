<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Helper;

use Doctrine\DBAL\Connection;
use Exception;

use function is_object;
use function is_string;
use function sprintf;

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
        try {
            $platform = $connection->getDatabasePlatform();
            // @phpstan-ignore function.alreadyNarrowedType (kept for older/mocked platforms)
            if (method_exists($platform, 'quoteSingleIdentifier')) {
                return $platform->quoteSingleIdentifier($identifier);
            }
        } catch (Exception) {
            // Fall through to connection or fallback
        }

        // Connection-level quoting (DBAL 3.x when not final)
        // @phpstan-ignore function.alreadyNarrowedType (DBAL version / mock differences)
        if (method_exists($connection, 'quoteSingleIdentifier')) {
            return $connection->quoteSingleIdentifier($identifier);
        }

        // Fallback for older DBAL versions: use quoteIdentifier (DBAL 2.x)
        // @phpstan-ignore function.alreadyNarrowedType (DBAL version / mock differences)
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
            if (str_contains(strtolower($platformName), 'sqlite')) {
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
                if (stripos($driverClass, 'sqlite') !== false) {
                    return 'pdo_sqlite';
                }
            }
        } catch (Exception) {
            // Fall through to default
        }

        // Last resort: default to mysql
        return 'pdo_mysql';
    }

    /**
     * Gets the unqualified name of a DBAL schema object (column, table, etc.).
     * Compatible with DBAL 2.x–4.x.
     *
     * @param object $asset A DBAL schema asset (e.g. Column, Table)
     *
     * @return string The object name
     */
    public static function getSchemaObjectName(object $asset): string
    {
        if (method_exists($asset, 'getObjectName')) {
            $objectName = $asset->getObjectName();
            if (is_object($objectName) && method_exists($objectName, 'toString')) {
                $name = $objectName->toString();
                if (is_string($name) && $name !== '') {
                    return $name;
                }
            }
        }

        if (method_exists($asset, 'getName')) {
            $name = $asset->getName();
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        throw new Exception(sprintf('Cannot resolve schema object name from %s', $asset::class));
    }

    /**
     * Gets the logical connection name in a compatible way across DBAL versions.
     *
     * @param Connection $connection The database connection
     *
     * @return string The connection name (e.g. 'default')
     */
    public static function getConnectionName(Connection $connection): string
    {
        if (method_exists($connection, 'getName')) {
            $name = $connection->getName();
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        /** @var array<string, mixed> $params */
        $params = $connection->getParams();
        if (isset($params['connectionName']) && is_string($params['connectionName']) && $params['connectionName'] !== '') {
            return $params['connectionName'];
        }

        if (isset($params['dbname']) && is_string($params['dbname']) && $params['dbname'] !== '') {
            return $params['dbname'];
        }

        throw new Exception(sprintf('Cannot resolve connection name from %s', $connection::class));
    }
}
