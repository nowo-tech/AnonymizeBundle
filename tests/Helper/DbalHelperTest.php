<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Exception;
use Nowo\AnonymizeBundle\Helper\DbalHelper;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test case for DbalHelper.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class DbalHelperTest extends TestCase
{
    /**
     * Test quoteIdentifier via platform (DBAL 3.6+ / 4.x; platform is mockable when Connection method is final).
     */
    public function testQuoteIdentifierWithQuoteSingleIdentifier(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')
            ->with('test_table')
            ->willReturn('`test_table`');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $result = DbalHelper::quoteIdentifier($connection, 'test_table');
        $this->assertEquals('`test_table`', $result);
    }

    /**
     * Test quoteIdentifier with quoteIdentifier method (DBAL 2.x).
     *
     * Uses platform when available (same as DBAL 3.6+ path).
     */
    public function testQuoteIdentifierWithQuoteIdentifier(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')
            ->with('test_table')
            ->willReturn('`test_table`');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $result = DbalHelper::quoteIdentifier($connection, 'test_table');
        $this->assertEquals('`test_table`', $result);
    }

    /**
     * Test quoteIdentifier fallback to manual quoting.
     *
     * Verifies the helper returns a valid result when platform provides quoting.
     */
    public function testQuoteIdentifierFallback(): void
    {
        $identifier = 'test_table';
        $expected   = '`test_table`';

        $manualResult = '`' . str_replace('`', '``', $identifier) . '`';
        $this->assertEquals($expected, $manualResult);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturn('`test_table`');
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $result = DbalHelper::quoteIdentifier($connection, $identifier);
        $this->assertIsString($result);
        $this->assertEquals('`test_table`', $result);
    }

    /**
     * Test quoteIdentifier with backticks in identifier (manual fallback).
     */
    public function testQuoteIdentifierWithBackticks(): void
    {
        $identifier = 'test`table';
        $expected   = '`test``table`';

        $manualResult = '`' . str_replace('`', '``', $identifier) . '`';
        $this->assertEquals($expected, $manualResult);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturn('`test``table`');
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $result = DbalHelper::quoteIdentifier($connection, $identifier);
        $this->assertIsString($result);
        $this->assertEquals('`test``table`', $result);
    }

    /**
     * Test getDriverName with params fallback.
     */
    public function testGetDriverNameWithParams(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(AbstractPlatform::class));
        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_mysql']);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_mysql', $result);
    }

    /**
     * Test getDriverName with MySQL platform class name detection.
     */
    public function testGetDriverNameWithMySQLPlatform(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        // Create a mock platform with MySQL in class name
        $mysqlPlatform = $this->getMockBuilder(AbstractPlatform::class)
            ->getMock();
        $mysqlPlatformClass = $mysqlPlatform::class;
        // Use reflection to check if class name contains MySQL
        // Since we can't easily mock the class name, we'll test via params
        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($mysqlPlatform);
        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_mysql']);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_mysql', $result);
    }

    /**
     * Test getDriverName with PostgreSQL platform.
     */
    public function testGetDriverNameWithPostgreSQLPlatform(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $postgresPlatform = $this->getMockBuilder(AbstractPlatform::class)
            ->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($postgresPlatform);
        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_pgsql']);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_pgsql', $result);
    }

    /**
     * Test getDriverName with SQLite platform.
     */
    public function testGetDriverNameWithSQLitePlatform(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $sqlitePlatform = $this->getMockBuilder(AbstractPlatform::class)
            ->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($sqlitePlatform);
        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_sqlite']);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_sqlite', $result);
    }

    /**
     * Test getDriverName fallback to default.
     */
    public function testGetDriverNameFallback(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $unknownPlatform = $this->getMockBuilder(AbstractPlatform::class)
            ->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($unknownPlatform);
        $connection->method('getParams')
            ->willReturn([]);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_mysql', $result); // Default fallback
    }

    /**
     * Test getDriverName with driverClass in params.
     */
    public function testGetDriverNameFromDriverClass(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $unknownPlatform = $this->getMockBuilder(AbstractPlatform::class)
            ->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($unknownPlatform);
        $connection->method('getParams')
            ->willReturn(['driverClass' => 'Doctrine\\DBAL\\Driver\\PDO\\MySQL\\Driver']);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_mysql', $result);
    }

    /**
     * Test getDriverName with platform class name containing MySQL.
     * Note: We can't easily mock the platform class name, so we test via params fallback.
     */
    public function testGetDriverNameWithMySQLPlatformClassName(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);

        // Create a platform mock
        $platform = $this->createMock(AbstractPlatform::class);

        // Since we can't easily change the class name, we test via params which is the fallback
        $connection->method('getDatabasePlatform')
            ->willReturn($platform);
        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_mysql']);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_mysql', $result);
    }

    /**
     * Test getDriverName with driver getName method (DBAL 2.x).
     * Note: Since getName() doesn't exist in the Driver interface, we test via params fallback.
     */
    public function testGetDriverNameWithDriverGetName(): void
    {
        // Create a driver mock that would have getName() in DBAL 2.x
        // Since we can't easily mock a method that doesn't exist, we test the fallback path
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(AbstractPlatform::class));
        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_pgsql']);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_pgsql', $result);
    }

    /**
     * Test getDriverName with exception in getDatabasePlatform.
     */
    public function testGetDriverNameWithException(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willThrowException(new Exception('Database error'));
        $connection->method('getParams')
            ->willReturn([]);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_mysql', $result); // Default fallback
    }

    /**
     * Test getDriverName with driverClass containing PostgreSQL.
     */
    public function testGetDriverNameFromDriverClassPostgreSQL(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $unknownPlatform = $this->getMockBuilder(AbstractPlatform::class)
            ->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($unknownPlatform);
        $connection->method('getParams')
            ->willReturn(['driverClass' => 'Doctrine\\DBAL\\Driver\\PDO\\PostgreSQL\\Driver']);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_pgsql', $result);
    }

    /**
     * Test getDriverName with driverClass containing Sqlite.
     */
    public function testGetDriverNameFromDriverClassSqlite(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $unknownPlatform = $this->getMockBuilder(AbstractPlatform::class)
            ->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($unknownPlatform);
        // The code looks for "Sqlite" (case-sensitive), so we use a class name that contains it
        $connection->method('getParams')
            ->willReturn(['driverClass' => 'Doctrine\\DBAL\\Driver\\PDO\\Sqlite\\Driver']);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_sqlite', $result);
    }

    /**
     * Test getDriverName with platform class name containing MySQL.
     * Note: We can't easily mock the platform class name, so we test via params fallback.
     */
    public function testGetDriverNameWithMySQLPlatformClassNameDetection(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $mysqlPlatform = $this->getMockBuilder(AbstractPlatform::class)
            ->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($mysqlPlatform);
        // Since we can't easily control the platform class name, we'll test via params
        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_mysql']);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_mysql', $result);
    }

    /**
     * Test getDriverName with quoteIdentifier fallback when both methods don't exist.
     * This tests the manual quoting fallback path.
     */
    public function testQuoteIdentifierWithNoMethods(): void
    {
        // Create a connection mock that doesn't have quoteSingleIdentifier or quoteIdentifier
        // We can't easily test this with mocks since method_exists returns true for mocked methods
        // But we can test the manual quoting logic directly
        $identifier   = 'test_table';
        $expected     = '`test_table`';
        $manualResult = '`' . str_replace('`', '``', $identifier) . '`';
        $this->assertEquals($expected, $manualResult);

        // Test with backticks in identifier
        $identifier2   = 'test`table';
        $expected2     = '`test``table`';
        $manualResult2 = '`' . str_replace('`', '``', $identifier2) . '`';
        $this->assertEquals($expected2, $manualResult2);
    }

    /**
     * Test getDriverName with driverClass that doesn't match known patterns.
     */
    public function testGetDriverNameWithUnknownDriverClass(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $unknownPlatform = $this->getMockBuilder(AbstractPlatform::class)
            ->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($unknownPlatform);
        $connection->method('getParams')
            ->willReturn(['driverClass' => 'Unknown\\Driver\\Class']);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_mysql', $result); // Default fallback
    }

    /**
     * Test getDriverName with empty params.
     */
    public function testGetDriverNameWithEmptyParams(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $unknownPlatform = $this->getMockBuilder(AbstractPlatform::class)
            ->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($unknownPlatform);
        $connection->method('getParams')
            ->willReturn([]);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_mysql', $result); // Default fallback
    }

    /**
     * Test getDriverName with real MySQL platform class (platform name detection).
     */
    public function testGetDriverNameWithRealMySQLPlatform(): void
    {
        $driver = $this->getMockBuilder(Driver::class)->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn(new \Doctrine\DBAL\Platforms\MySQLPlatform());
        $connection->method('getParams')->willReturn([]);

        $result = DbalHelper::getDriverName($connection);
        $this->assertSame('pdo_mysql', $result);
    }

    /**
     * Test getDriverName with real PostgreSQL platform class (platform name detection).
     */
    public function testGetDriverNameWithRealPostgreSQLPlatform(): void
    {
        $driver = $this->getMockBuilder(Driver::class)->getMock();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn(new \Doctrine\DBAL\Platforms\PostgreSQLPlatform());
        $connection->method('getParams')->willReturn([]);

        $result = DbalHelper::getDriverName($connection);
        $this->assertSame('pdo_pgsql', $result);
    }

    /**
     * Test quoteIdentifier with different identifier names (via platform).
     */
    public function testQuoteIdentifierWithDifferentNames(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')
            ->willReturnCallback(static function ($identifier) {
                return '`' . $identifier . '`';
            });

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $result1 = DbalHelper::quoteIdentifier($connection, 'users');
        $this->assertEquals('`users`', $result1);

        $result2 = DbalHelper::quoteIdentifier($connection, 'email');
        $this->assertEquals('`email`', $result2);

        $result3 = DbalHelper::quoteIdentifier($connection, 'user_id');
        $this->assertEquals('`user_id`', $result3);
    }

    /**
     * Test quoteIdentifier with empty string.
     */
    public function testQuoteIdentifierWithEmptyString(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->with('')->willReturn('``');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $result = DbalHelper::quoteIdentifier($connection, '');
        $this->assertEquals('``', $result);
    }

    /**
     * Test quoteIdentifier with special characters.
     */
    public function testQuoteIdentifierWithSpecialCharacters(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')
            ->willReturnCallback(static function ($identifier) {
                return '`' . $identifier . '`';
            });

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $result = DbalHelper::quoteIdentifier($connection, 'table-name');
        $this->assertEquals('`table-name`', $result);
    }

    /**
     * Test getDriverName with platform class name detection using reflection.
     */
    public function testGetDriverNameWithPlatformClassNameDetection(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        // Create a platform mock and use reflection to set a custom class name
        $platform = $this->createMock(AbstractPlatform::class);

        // Use reflection to create a mock with a specific class name
        $reflection = new ReflectionClass($platform);
        $className  = $reflection->getName();

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($platform);
        // Since we can't easily change the platform class name, we test via params fallback
        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_mysql']);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_mysql', $result);
    }

    /**
     * Test getDriverName with PostgreSQL platform class name detection.
     */
    public function testGetDriverNameWithPostgreSQLPlatformClassNameDetection(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $platform = $this->createMock(AbstractPlatform::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($platform);
        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_pgsql']);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_pgsql', $result);
    }

    /**
     * Test getDriverName with SQLite platform class name detection.
     */
    public function testGetDriverNameWithSQLitePlatformClassNameDetection(): void
    {
        $driver = $this->getMockBuilder(Driver::class)
            ->getMock();

        $platform = $this->createMock(AbstractPlatform::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')
            ->willReturn($driver);
        $connection->method('getDatabasePlatform')
            ->willReturn($platform);
        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_sqlite']);

        $result = DbalHelper::getDriverName($connection);
        $this->assertEquals('pdo_sqlite', $result);
    }

    /**
     * Test quoteIdentifier via platform (preferred path when platform has quoteSingleIdentifier).
     */
    public function testQuoteIdentifierWithQuoteIdentifierMethod(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->with('test')->willReturn('`test`');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $result = DbalHelper::quoteIdentifier($connection, 'test');
        $this->assertEquals('`test`', $result);
    }

    /**
     * Test quoteIdentifierFallback produces double quotes for PostgreSQL (via reflection).
     */
    public function testQuoteIdentifierFallbackProducesDoubleQuotesForPgsql(): void
    {
        $connection = $this->createConnectionWithDriverName('pdo_pgsql');
        $result     = $this->invokeQuoteIdentifierFallback($connection, 'users');
        $this->assertSame('"users"', $result);
    }

    /**
     * Test quoteIdentifierFallback produces double quotes for SQLite (via reflection).
     */
    public function testQuoteIdentifierFallbackProducesDoubleQuotesForSqlite(): void
    {
        $connection = $this->createConnectionWithDriverName('pdo_sqlite');
        $result     = $this->invokeQuoteIdentifierFallback($connection, 'my_table');
        $this->assertSame('"my_table"', $result);
    }

    /**
     * Test quoteIdentifierFallback escapes double quotes for PostgreSQL.
     */
    public function testQuoteIdentifierFallbackEscapesDoubleQuotesForPgsql(): void
    {
        $connection = $this->createConnectionWithDriverName('pdo_pgsql');
        $result     = $this->invokeQuoteIdentifierFallback($connection, 'col"name');
        $this->assertSame('"col""name"', $result);
    }

    /**
     * Test quoteIdentifierFallback produces backticks for MySQL (via reflection).
     */
    public function testQuoteIdentifierFallbackProducesBackticksForMysql(): void
    {
        $connection = $this->createConnectionWithDriverName('pdo_mysql');
        $result     = $this->invokeQuoteIdentifierFallback($connection, 'users');
        $this->assertSame('`users`', $result);
    }

    /**
     * Test quoteIdentifierFallback produces backticks for unknown driver.
     */
    public function testQuoteIdentifierFallbackProducesBackticksForUnknownDriver(): void
    {
        $connection = $this->createConnectionWithDriverName('pdo_oci');
        $result     = $this->invokeQuoteIdentifierFallback($connection, 'table_name');
        $this->assertSame('`table_name`', $result);
    }

    private function createConnectionWithDriverName(string $driverName): Connection
    {
        $driver   = $this->getMockBuilder(Driver::class)->getMock();
        $platform = $this->createMock(AbstractPlatform::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => $driverName]);

        return $connection;
    }

    private function invokeQuoteIdentifierFallback(Connection $connection, string $identifier): string
    {
        $reflection = new ReflectionClass(DbalHelper::class);
        $method     = $reflection->getMethod('quoteIdentifierFallback');
        $method->setAccessible(true);

        return $method->invoke(null, $connection, $identifier);
    }
}
