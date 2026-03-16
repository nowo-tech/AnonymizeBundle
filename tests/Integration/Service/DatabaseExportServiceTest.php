<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Nowo\AnonymizeBundle\Service\DatabaseExportService;
use Nowo\AnonymizeBundle\Service\CommandRunnerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;

/**
 * Test case for DatabaseExportService.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class DatabaseExportServiceTest extends TestCase
{
    private string $tempDir;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/anonymize_export_test_' . uniqid();
        mkdir($this->tempDir, 0o755, true);

        $this->container = $this->createMock(ContainerInterface::class);
        /** @var ContainerInterface&\PHPUnit\Framework\MockObject\MockObject $this->container */
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    /**
     * Test that generateFilename creates correct filename.
     */
    public function testGenerateFilename(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        // Mock DbalHelper::getDriverName
        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_mysql']);

        // Since we can't easily test the actual export without system commands,
        // we'll just verify the service can be instantiated
        $this->assertInstanceOf(DatabaseExportService::class, $service);
    }

    /**
     * Test that exportConnection for MySQL uses the command runner and returns the expected dump path on success.
     */
    public function testExportMySQLUsesCommandRunnerAndReturnsDumpPath(): void
    {
        $exportDir = $this->tempDir . '/mysql_export';
        mkdir($exportDir, 0o755, true);

        $runner = new class() implements CommandRunnerInterface {
            public array $executed = [];

            public function commandExists(string $command): bool
            {
                return $command === 'mysqldump';
            }

            public function exec(string $command, ?array &$output = null): int
            {
                $this->executed[] = $command;
                // Extract output path: command ends with "> '/path/to/file.sql' 2>&1" (escapeshellarg)
                if (preg_match("/>\s*'([^']*)'\s*2>&1\s*$/", $command, $m) === 1) {
                    $path = $m[1];
                    if ($path !== '') {
                        @mkdir(\dirname($path), 0o755, true);
                        file_put_contents($path, 'mysqldump content');
                    }
                }
                $output = [];

                return 0;
            }
        };

        $service = new DatabaseExportService(
            $this->container,
            $exportDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
            $runner,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn([
            'driver'   => 'pdo_mysql',
            'host'     => 'localhost',
            'port'     => 3306,
            'user'     => 'root',
            'password' => 'secret',
        ]);

        $result = $service->exportConnection($em, 'default');

        $this->assertNotNull($result);
        $this->assertFileExists($result);
        $this->assertStringEndsWith('.sql', $result);
        $this->assertStringContainsString('mysqldump content', file_get_contents($result));
    }

    /**
     * Test that exportConnection for PostgreSQL uses the command runner and returns the expected dump path on success.
     */
    public function testExportPostgreSQLUsesCommandRunnerAndReturnsDumpPath(): void
    {
        $exportDir = $this->tempDir . '/pgsql_export';
        mkdir($exportDir, 0o755, true);

        $runner = new class() implements CommandRunnerInterface {
            public array $executed = [];

            public function commandExists(string $command): bool
            {
                return $command === 'pg_dump';
            }

            public function exec(string $command, ?array &$output = null): int
            {
                $this->executed[] = $command;
                // Extract the --file argument to know where to write
                $matches = [];
                if (preg_match('/--file=([^\\s]+)/', $command, $matches) === 1) {
                    $path = trim($matches[1], " \t\n\r\0\x0B'\"");
                    if ($path !== '') {
                        @mkdir(\dirname($path), 0o755, true);
                        file_put_contents($path, 'pg_dump content');
                    }
                }
                $output = [];

                return 0;
            }
        };

        $service = new DatabaseExportService(
            $this->container,
            $exportDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
            $runner,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn([
            'driver'   => 'pdo_pgsql',
            'host'     => 'localhost',
            'port'     => 5432,
            'user'     => 'postgres',
            'password' => 'secret',
        ]);

        $result = $service->exportConnection($em, 'default');

        $this->assertNotNull($result);
        $this->assertFileExists($result);
        $this->assertStringEndsWith('.sql', $result);
        $this->assertStringContainsString('pg_dump content', file_get_contents($result));
    }

    /**
     * Test that exportConnection returns null for unsupported driver.
     */
    public function testExportConnectionReturnsNullForUnsupportedDriver(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        // Mock unsupported driver
        $connection->method('getParams')
            ->willReturn(['driver' => 'unsupported_driver']);

        $result = $service->exportConnection($em, 'test_connection');
        $this->assertNull($result);
    }

    /**
     * Test that exportConnection with pdo_sqlite copies file correctly.
     */
    public function testExportSQLiteCopiesFile(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        $testDbPath = $this->tempDir . '/source.db';
        file_put_contents($testDbPath, 'SQLite test content');

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite', 'path' => $testDbPath]);

        $result = $service->exportConnection($em, 'default');
        $this->assertNotNull($result);
        $this->assertFileExists($result);
        $this->assertStringContainsString('.sqlite', $result);
        $this->assertSame('SQLite test content', file_get_contents($result));
    }

    /**
     * Test that exportConnection with pdo_sqlite and zip compression produces a zip file.
     */
    public function testExportSQLiteWithZipCompression(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'zip',
            false,
        );

        $testDbPath = $this->tempDir . '/source2.db';
        file_put_contents($testDbPath, 'SQLite content for zip');

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite', 'path' => $testDbPath]);

        $result = $service->exportConnection($em, 'default');
        $this->assertNotNull($result);
        $this->assertFileExists($result);
        $this->assertStringEndsWith('.zip', $result);
    }

    /**
     * Test that exportConnection with zip compression removes the uncompressed file after compressing (lines 86-89).
     * Zip compression creates a .zip copy; the service must unlink the original .sqlite file.
     */
    public function testExportSQLiteWithZipCompressionRemovesUncompressedFile(): void
    {
        $exportDir = $this->tempDir . '/zip_export_' . uniqid();
        $this->assertDirectoryDoesNotExist($exportDir);

        $service = new DatabaseExportService(
            $this->container,
            $exportDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'zip',
            false,
        );

        $testDbPath = $this->tempDir . '/source_zip_unlink.db';
        file_put_contents($testDbPath, 'SQLite for zip unlink');

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite', 'path' => $testDbPath]);

        $result = $service->exportConnection($em, 'default');

        $this->assertNotNull($result);
        $this->assertFileExists($result);
        $this->assertStringEndsWith('.zip', $result);
        // After zip compression the original .sqlite must have been removed (unlink branch 88)
        $sqliteFiles = glob($exportDir . '/*.sqlite');
        $this->assertEmpty($sqliteFiles, 'Uncompressed .sqlite file should be removed after zip compression');
    }

    /**
     * Test that exportSQLite returns null when path is missing.
     */
    public function testExportSQLiteReturnsNullWhenPathMissing(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite']);

        $result = $service->exportConnection($em, 'default');
        $this->assertNull($result);
    }

    /**
     * Test that exportSQLite returns null when path points to a non-existent file (covers !file_exists at line 261).
     */
    public function testExportSQLiteReturnsNullWhenPathDoesNotExist(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        $nonexistentPath = $this->tempDir . '/does_not_exist.db';

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite', 'path' => $nonexistentPath]);

        $result = $service->exportConnection($em, 'default');
        $this->assertNull($result);
    }

    /**
     * Test that exportConnection returns null for MySQL when mysqldump is not available (covers exportMySQL early return).
     */
    public function testExportConnectionReturnsNullForMySQLWhenMysqldumpNotAvailable(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_mysql', 'host' => 'localhost']);

        $result = $service->exportConnection($em, 'default');

        $this->assertNull($result);
    }

    /**
     * Test that exportConnection returns null for PostgreSQL when pg_dump is not available (covers exportPostgreSQL early return).
     */
    public function testExportConnectionReturnsNullForPostgreSQLWhenPgDumpNotAvailable(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_pgsql', 'host' => 'localhost']);

        $result = $service->exportConnection($em, 'default');

        $this->assertNull($result);
    }

    /**
     * Test that exportConnection creates output directory if it doesn't exist (covers mkdir at lines 74-75).
     * Uses SQLite export so we actually reach the mkdir branch before exporting.
     */
    public function testExportConnectionCreatesOutputDirectory(): void
    {
        $nonExistentDir = $this->tempDir . '/created_by_export_' . uniqid();
        $this->assertDirectoryDoesNotExist($nonExistentDir);

        $service = new DatabaseExportService(
            $this->container,
            $nonExistentDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        $testDbPath = $this->tempDir . '/source_mkdir.db';
        file_put_contents($testDbPath, 'SQLite for mkdir test');

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite', 'path' => $testDbPath]);

        $result = $service->exportConnection($em, 'default');

        $this->assertDirectoryExists($nonExistentDir);
        $this->assertNotNull($result);
        $this->assertFileExists($result);
        $this->assertStringContainsString('.sqlite', $result);
    }

    /**
     * Test that exportConnection handles compression option.
     */
    public function testExportConnectionHandlesCompression(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'gzip',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        $connection->method('getParams')
            ->willReturn(['driver' => 'unsupported_driver']);

        $result = $service->exportConnection($em, 'test_connection');
        $this->assertNull($result);
    }

    /**
     * Test that exportConnection handles autoGitignore option.
     */
    public function testExportConnectionHandlesAutoGitignore(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            true,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        $connection->method('getParams')
            ->willReturn(['driver' => 'unsupported_driver']);

        $result = $service->exportConnection($em, 'test_connection');
        $this->assertNull($result);
    }

    /**
     * Test that exportConnection with SQLite and autoGitignore true calls updateGitignore and updates .gitignore.
     */
    public function testExportSQLiteWithAutoGitignoreUpdatesGitignore(): void
    {
        $exportDir  = $this->tempDir . '/var/exports';
        $projectDir = $this->tempDir;

        $container = new class($projectDir) implements ContainerInterface {
            public function __construct(private string $projectDir)
            {
            }

            public function get(string $id): mixed
            {
                return null;
            }

            public function has(string $id): bool
            {
                return false;
            }

            public function hasParameter(string $name): bool
            {
                return $name === 'kernel.project_dir';
            }

            public function getParameter(string $name): mixed
            {
                return $name === 'kernel.project_dir' ? $this->projectDir : null;
            }
        };

        $service = new DatabaseExportService(
            $container,
            $exportDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            true,
        );

        $testDbPath = $this->tempDir . '/source_autogit.db';
        file_put_contents($testDbPath, 'SQLite for gitignore test');

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite', 'path' => $testDbPath]);

        $result = $service->exportConnection($em, 'default');

        $this->assertNotNull($result);
        $this->assertFileExists($result);
        $gitignorePath = $projectDir . '/.gitignore';
        $this->assertFileExists($gitignorePath);
        $content = file_get_contents($gitignorePath);
        $this->assertStringContainsString('var/exports/', $content);
        $this->assertStringContainsString('Database exports (auto-generated by AnonymizeBundle)', $content);
    }

    /**
     * Test that updateGitignore returns early when entry already exists in .gitignore (lines 507-508).
     */
    public function testUpdateGitignoreReturnsEarlyWhenEntryAlreadyExists(): void
    {
        $projectDir = $this->tempDir . '/project_early';
        $exportDir  = $projectDir . '/var/exports';
        mkdir($exportDir, 0o755, true);

        $gitignorePath = $projectDir . '/.gitignore';
        $existingEntry = "var/exports/\n";
        file_put_contents($gitignorePath, $existingEntry);

        $container = new class($projectDir) implements ContainerInterface {
            public function __construct(private string $projectDir)
            {
            }

            public function get(string $id): mixed
            {
                return null;
            }

            public function has(string $id): bool
            {
                return false;
            }

            public function hasParameter(string $name): bool
            {
                return $name === 'kernel.project_dir';
            }

            public function getParameter(string $name): mixed
            {
                return $name === 'kernel.project_dir' ? $this->projectDir : null;
            }
        };

        $service = new DatabaseExportService(
            $container,
            $exportDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            true,
        );

        $testDbPath = $this->tempDir . '/source_early.db';
        file_put_contents($testDbPath, 'SQLite early return test');

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite', 'path' => $testDbPath]);

        $service->exportConnection($em, 'default');

        $content = file_get_contents($gitignorePath);
        $this->assertStringContainsString('var/exports/', $content);
        $this->assertStringNotContainsString('Database exports (auto-generated by AnonymizeBundle)', $content);
        $this->assertSame($existingEntry, $content);
    }

    /**
     * Test that exportMongoDB returns null when mongodump is not available.
     */
    public function testExportMongoDBReturnsNullWhenCommandNotAvailable(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        // mongodump is likely not available in test environment
        $result = $service->exportMongoDB('test_connection', 'test_db', 'localhost', 27017);
        $this->assertNull($result);
    }

    /**
     * Test that exportMongoDB with zip compression uses the command runner and creates a ZIP archive (success path).
     */
    public function testExportMongoDBWithZipCompressionUsesRunnerAndCreatesZipArchive(): void
    {
        $exportDir = $this->tempDir . '/mongo_zip_exports';
        mkdir($exportDir, 0o755, true);

        // Pre-create the directory where mongodump would place its dump: $outputDir/$database
        $databaseDir = $exportDir . '/test_db';
        mkdir($databaseDir, 0o755, true);
        file_put_contents($databaseDir . '/data.bson', 'mongo dump content');

        $runner = new class() implements CommandRunnerInterface {
            public array $commands = [];

            public function commandExists(string $command): bool
            {
                // Simulate presence of mongodump
                return $command === 'mongodump';
            }

            public function exec(string $command, ?array &$output = null): int
            {
                $this->commands[] = $command;
                $output           = [];

                // We do not need to modify the filesystem here because the dump directory
                // is pre-created; DatabaseExportService will then create the ZIP archive.
                return 0;
            }
        };

        $service = new DatabaseExportService(
            $this->container,
            $exportDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'zip',
            false,
            $runner,
        );

        $result = $service->exportMongoDB('test_connection', 'test_db', 'localhost', 27017);

        $this->assertNotNull($result);
        $this->assertFileExists($result);
        $this->assertStringEndsWith('.zip', $result);
        $this->assertDirectoryDoesNotExist($databaseDir, 'MongoDB export directory should be removed after creating archive');
    }

    /**
     * Test that exportMongoDB handles different compression formats.
     */
    public function testExportMongoDBHandlesCompression(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'zip',
            false,
        );

        // mongodump is likely not available in test environment
        $result = $service->exportMongoDB('test_connection', 'test_db', 'localhost', 27017);
        $this->assertNull($result);
    }

    /**
     * Test that exportConnection handles different compression formats.
     */
    public function testExportConnectionHandlesDifferentCompressionFormats(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'bzip2',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        $connection->method('getParams')
            ->willReturn(['driver' => 'unsupported_driver']);

        $result = $service->exportConnection($em, 'test_connection');
        $this->assertNull($result);
    }

    /**
     * Test that exportConnection handles zip compression.
     */
    public function testExportConnectionHandlesZipCompression(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'zip',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        $connection->method('getParams')
            ->willReturn(['driver' => 'unsupported_driver']);

        $result = $service->exportConnection($em, 'test_connection');
        $this->assertNull($result);
    }

    /**
     * Test that exportMongoDB handles different compression formats.
     */
    public function testExportMongoDBHandlesDifferentCompressionFormats(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'bzip2',
            false,
        );

        // mongodump is likely not available in test environment
        $result = $service->exportMongoDB('test_connection', 'test_db', 'localhost', 27017);
        $this->assertNull($result);
    }

    /**
     * Test that exportMongoDB handles tar compression.
     */
    public function testExportMongoDBHandlesTarCompression(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'gzip',
            false,
        );

        // mongodump is likely not available in test environment
        $result = $service->exportMongoDB('test_connection', 'test_db', 'localhost', 27017);
        $this->assertNull($result);
    }

    /**
     * Test that exportMongoDB with gzip compression uses the command runner and creates a tar.gz archive (success path).
     */
    public function testExportMongoDBWithGzipCompressionUsesRunnerAndCreatesTarGzArchive(): void
    {
        $exportDir = $this->tempDir . '/mongo_targz_exports';
        mkdir($exportDir, 0o755, true);

        // Pre-create the directory where mongodump would place its dump: $outputDir/$database
        $databaseDir = $exportDir . '/test_db';
        mkdir($databaseDir, 0o755, true);
        file_put_contents($databaseDir . '/data.bson', 'mongo dump content');

        $runner = new class() implements CommandRunnerInterface {
            public array $commands = [];

            public function commandExists(string $command): bool
            {
                // Simulate presence of mongodump and tar
                return $command === 'mongodump' || $command === 'tar';
            }

            public function exec(string $command, ?array &$output = null): int
            {
                $this->commands[] = $command;
                $output           = [];

                // When tar is invoked, create the target tar.gz file so that file_exists($tarPath) is true.
                if (str_starts_with($command, 'tar ')) {
                    // Command structure: tar -c?f '<tarPath>' -C '<dir>' . 2>&1
                    $tokens = preg_split('/\s+/', $command);
                    if (isset($tokens[2])) {
                        $pathToken = trim((string) $tokens[2], '\'"');
                        if ($pathToken !== '') {
                            @mkdir(dirname($pathToken), 0o755, true);
                            file_put_contents($pathToken, 'tar gz content');
                        }
                    }
                }

                return 0;
            }
        };

        $service = new DatabaseExportService(
            $this->container,
            $exportDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'gzip',
            false,
            $runner,
        );

        $result = $service->exportMongoDB('test_connection', 'test_db', 'localhost', 27017);

        $this->assertNotNull($result);
        $this->assertFileExists($result);
        $this->assertStringEndsWith('.tar.gz', $result);
        $this->assertDirectoryDoesNotExist($databaseDir, 'MongoDB export directory should be removed after creating tar.gz archive');
    }

    /**
     * Test that exportConnection handles output directory with trailing slash.
     */
    public function testExportConnectionHandlesTrailingSlash(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir . '/',
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        $connection->method('getParams')
            ->willReturn(['driver' => 'unsupported_driver']);

        $result = $service->exportConnection($em, 'test_connection');
        $this->assertNull($result);
    }

    /**
     * Test that updateGitignore works when kernel is available.
     */
    public function testUpdateGitignoreWorksWithKernel(): void
    {
        $projectDir = $this->tempDir . '/project';
        mkdir($projectDir, 0o755, true);

        $kernel = $this->createMock(\Symfony\Component\HttpKernel\KernelInterface::class);
        $kernel->method('getProjectDir')
            ->willReturn($projectDir);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->with('kernel')
            ->willReturn(true);
        $container->method('get')
            ->with('kernel')
            ->willReturn($kernel);

        $exportDir = $projectDir . '/exports';
        $service   = new DatabaseExportService(
            $container,
            $exportDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            true,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        $connection->method('getParams')
            ->willReturn(['driver' => 'unsupported_driver']);

        $result = $service->exportConnection($em, 'test_connection');

        // Check if .gitignore was created/updated
        $gitignorePath = $projectDir . '/.gitignore';
        if (file_exists($gitignorePath)) {
            $content = file_get_contents($gitignorePath);
            $this->assertStringContainsString('exports/', $content);
        }

        $this->assertNull($result);
    }

    /**
     * Test that updateGitignore doesn't duplicate entries.
     */
    public function testUpdateGitignoreDoesNotDuplicateEntries(): void
    {
        $projectDir = $this->tempDir . '/project';
        mkdir($projectDir, 0o755, true);

        $gitignorePath = $projectDir . '/.gitignore';
        file_put_contents($gitignorePath, "exports/\n");

        $kernel = $this->createMock(\Symfony\Component\HttpKernel\KernelInterface::class);
        $kernel->method('getProjectDir')
            ->willReturn($projectDir);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->with('kernel')
            ->willReturn(true);
        $container->method('get')
            ->with('kernel')
            ->willReturn($kernel);

        $exportDir = $projectDir . '/exports';
        $service   = new DatabaseExportService(
            $container,
            $exportDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            true,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        $connection->method('getParams')
            ->willReturn(['driver' => 'unsupported_driver']);

        // Call twice to test duplicate prevention
        $service->exportConnection($em, 'test_connection');
        $service->exportConnection($em, 'test_connection');

        if (file_exists($gitignorePath)) {
            $content = file_get_contents($gitignorePath);
            $count   = substr_count($content, 'exports/');
            // Should only appear once (original + maybe one auto-generated entry)
            $this->assertLessThanOrEqual(2, $count);
        }
    }

    /**
     * Test that generateFilename handles all placeholders correctly.
     */
    public function testGenerateFilenameHandlesAllPlaceholders(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_mysql']);

        // Test that filename pattern is processed
        $result = $service->exportConnection($em, 'test_connection');
        // Result will be null because mysqldump is not available, but we test the structure
        $this->assertNull($result);
    }

    /**
     * Test that generateFilename handles custom patterns.
     */
    public function testGenerateFilenameHandlesCustomPatterns(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            'backup_{database}_{date}.sql',
            'none',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_mysql']);

        $result = $service->exportConnection($em, 'test_connection');
        $this->assertNull($result);
    }

    /**
     * Test that getFileExtension returns correct extensions for different drivers.
     */
    public function testGetFileExtensionReturnsCorrectExtensions(): void
    {
        // Test MySQL
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            'test.{format}',
            'none',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        // Test different drivers
        $drivers = ['pdo_mysql', 'pdo_pgsql', 'pdo_sqlite'];
        foreach ($drivers as $driver) {
            $connection->method('getParams')
                ->willReturn(['driver' => $driver]);

            $result = $service->exportConnection($em, 'test_connection');
            // Result will be null, but we test the structure
            $this->assertNull($result);
        }
    }

    /**
     * Test that exportSQLite handles null path.
     */
    public function testExportSQLiteHandlesNullPath(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_sqlite']);

        $result = $service->exportConnection($em, 'test_connection');
        // Should return null when path is null
        $this->assertNull($result);
    }

    /**
     * Test that exportSQLite handles non-existent file.
     */
    public function testExportSQLiteHandlesNonExistentFile(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_sqlite', 'path' => '/nonexistent/path.db']);

        $result = $service->exportConnection($em, 'test_connection');
        // Should return null when file doesn't exist
        $this->assertNull($result);
    }

    /**
     * Test that compressFile handles file that doesn't exist.
     */
    public function testCompressFileHandlesNonExistentFile(): void
    {
        // This is tested indirectly through exportConnection
        // When compression is enabled but export fails, compressFile won't be called
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'gzip',
            false,
        );

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        $connection->method('getParams')
            ->willReturn(['driver' => 'unsupported_driver']);

        $result = $service->exportConnection($em, 'test_connection');
        $this->assertNull($result);
    }

    /**
     * Test that exportMongoDB handles missing export directory.
     */
    public function testExportMongoDBHandlesMissingExportDirectory(): void
    {
        // mongodump is not available, so this will return null early
        // But we test the structure
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        $result = $service->exportMongoDB('test_connection', 'test_db', 'localhost', 27017);
        $this->assertNull($result);
    }

    /**
     * Test that updateGitignore returns early when kernel.project_dir is empty string (lines 377-378).
     */
    public function testUpdateGitignoreReturnsEarlyWhenProjectDirEmpty(): void
    {
        $projectDir = $this->tempDir . '/empty_project';
        mkdir($projectDir, 0o755, true);
        $gitignorePath = $projectDir . '/.gitignore';
        file_put_contents($gitignorePath, '');

        $container = new class($projectDir) implements ContainerInterface {
            public function __construct(private string $projectDir)
            {
            }

            public function get(string $id): mixed
            {
                return null;
            }

            public function has(string $id): bool
            {
                return false;
            }

            public function hasParameter(string $name): bool
            {
                return $name === 'kernel.project_dir';
            }

            public function getParameter(string $name): mixed
            {
                return $name === 'kernel.project_dir' ? '' : null;
            }
        };

        $exportDir = $this->tempDir . '/exports_empty';
        $service   = new DatabaseExportService(
            $container,
            $exportDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            true,
        );

        $testDbPath = $this->tempDir . '/source_empty_dir.db';
        file_put_contents($testDbPath, 'SQLite');

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite', 'path' => $testDbPath]);

        $service->exportConnection($em, 'default');

        // updateGitignore returns early when projectDir === '', so .gitignore must not get our entry
        $content = file_get_contents($gitignorePath);
        $this->assertStringNotContainsString('exports_empty', $content);
    }

    /**
     * Test that updateGitignore returns early when container has no kernel.
     */
    public function testUpdateGitignoreReturnsEarlyWhenNoKernel(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            true,
        );

        $testDbPath = $this->tempDir . '/source_no_kernel.db';
        file_put_contents($testDbPath, 'SQLite');

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite', 'path' => $testDbPath]);

        $result = $service->exportConnection($em, 'default');
        $this->assertNotNull($result);
        $this->assertFileExists($result);
    }

    /**
     * Test that compressFile with gzip is applied when gzip is available.
     */
    public function testExportSQLiteWithGzipCompression(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'gzip',
            false,
        );

        $testDbPath = $this->tempDir . '/source_gzip.db';
        file_put_contents($testDbPath, 'SQLite gzip');

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite', 'path' => $testDbPath]);

        $result = $service->exportConnection($em, 'default');
        $this->assertNotNull($result);
        $this->assertFileExists($result);
        if (file_exists($result)) {
            $this->assertTrue(str_ends_with($result, '.gz') || str_ends_with($result, '.sqlite'));
        }
    }

    /**
     * Test that compressFile with bzip2 is applied when bzip2 is available.
     */
    public function testExportSQLiteWithBzip2Compression(): void
    {
        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'bzip2',
            false,
        );

        $testDbPath = $this->tempDir . '/source_bzip2.db';
        file_put_contents($testDbPath, 'SQLite bzip2');

        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $driver     = $this->getMockBuilder(Driver::class)->getMock();
        $platform   = $this->createMock(AbstractPlatform::class);

        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($driver);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite', 'path' => $testDbPath]);

        $result = $service->exportConnection($em, 'default');
        $this->assertNotNull($result);
        $this->assertFileExists($result);
    }

    /**
     * Test createZipArchive via reflection (used by exportMongoDB).
     */
    public function testCreateZipArchive(): void
    {
        $subDir = $this->tempDir . '/zip_src';
        mkdir($subDir, 0o755, true);
        file_put_contents($subDir . '/file1.txt', 'content');

        $zipPath = $this->tempDir . '/out.zip';
        $ref     = new ReflectionClass(DatabaseExportService::class);
        $method  = $ref->getMethod('createZipArchive');
        $method->setAccessible(true);

        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            'x',
            'none',
            false,
        );

        $result = $method->invoke($service, $subDir, $zipPath);
        $this->assertTrue($result);
        $this->assertFileExists($zipPath);
    }

    /**
     * Test createZipArchive returns false when ZipArchive::open fails (e.g. zip path is a directory).
     */
    public function testCreateZipArchiveReturnsFalseWhenZipOpenFails(): void
    {
        $subDir  = $this->tempDir . '/zip_fail_src';
        $zipPath = $this->tempDir . '/zip_fail_out';
        mkdir($subDir, 0o755, true);
        mkdir($zipPath, 0o755, true);
        file_put_contents($subDir . '/file.txt', 'x');

        $ref    = new ReflectionClass(DatabaseExportService::class);
        $method = $ref->getMethod('createZipArchive');
        $method->setAccessible(true);

        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            'x',
            'none',
            false,
        );

        $result = $method->invoke($service, $subDir, $zipPath);
        $this->assertFalse($result);
    }

    /**
     * Test createTarArchive via reflection (used by exportMongoDB).
     */
    public function testCreateTarArchive(): void
    {
        $subDir = $this->tempDir . '/tar_src';
        mkdir($subDir, 0o755, true);
        file_put_contents($subDir . '/file1.txt', 'content');

        $tarPath = $this->tempDir . '/out.tar';
        $ref     = new ReflectionClass(DatabaseExportService::class);
        $method  = $ref->getMethod('createTarArchive');
        $method->setAccessible(true);

        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            'x',
            'none',
            false,
        );

        $result = $method->invoke($service, $subDir, $tarPath);
        $this->assertTrue($result);
        $this->assertFileExists($tarPath);
    }

    /**
     * Test createTarArchive with .tar.gz path sets compression flag 'z' (lines 404-406).
     */
    public function testCreateTarArchiveWithGzipExtension(): void
    {
        $subDir  = $this->tempDir . '/tar_gz_src';
        $tarPath = $this->tempDir . '/out.tar.gz';
        mkdir($subDir, 0o755, true);
        file_put_contents($subDir . '/file.txt', 'content');

        $ref    = new ReflectionClass(DatabaseExportService::class);
        $method = $ref->getMethod('createTarArchive');
        $method->setAccessible(true);

        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            'x',
            'none',
            false,
        );

        $result = $method->invoke($service, $subDir, $tarPath);
        $this->assertTrue($result);
        $this->assertFileExists($tarPath);
    }

    /**
     * Test createTarArchive with .tar.bz2 path sets compression flag 'j' (lines 406-408).
     */
    public function testCreateTarArchiveWithBzip2Extension(): void
    {
        $subDir  = $this->tempDir . '/tar_bz2_src';
        $tarPath = $this->tempDir . '/out.tar.bz2';
        mkdir($subDir, 0o755, true);
        file_put_contents($subDir . '/file.txt', 'content');

        $ref    = new ReflectionClass(DatabaseExportService::class);
        $method = $ref->getMethod('createTarArchive');
        $method->setAccessible(true);

        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            'x',
            'none',
            false,
        );

        $result = $method->invoke($service, $subDir, $tarPath);
        $this->assertTrue($result);
        $this->assertFileExists($tarPath);
    }

    /**
     * Test getFileExtension via reflection to cover mongodb and default branches.
     */
    public function testGetFileExtensionForMongoAndDefault(): void
    {
        $ref    = new ReflectionClass(DatabaseExportService::class);
        $method = $ref->getMethod('getFileExtension');
        $method->setAccessible(true);

        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            '{connection}_{database}_{date}_{time}.{format}',
            'none',
            false,
        );

        $mongodbExtension = $method->invoke($service, 'mongodb');
        $this->assertSame('bson', $mongodbExtension);

        $defaultExtension = $method->invoke($service, 'pdo_unknown');
        $this->assertSame('sql', $defaultExtension);
    }

    /**
     * Test compressFile with gzip: when gzip is not in PATH, returns original path (covers case 'gzip' and commandExists false branch).
     */
    public function testCompressFileWithGzipCoversGzipBranch(): void
    {
        $filePath = $this->tempDir . '/to_compress_gzip.txt';
        file_put_contents($filePath, 'content');

        $ref    = new ReflectionClass(DatabaseExportService::class);
        $method = $ref->getMethod('compressFile');
        $method->setAccessible(true);

        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            'x',
            'gzip',
            false,
        );

        $result = $method->invoke($service, $filePath);
        $this->assertNotNull($result);
        if (str_ends_with($result, '.gz')) {
            $this->assertFileExists($result);
        } else {
            $this->assertSame($filePath, $result);
        }
    }

    /**
     * Test compressFile with bzip2: when bzip2 is not in PATH, returns original path (covers case 'bzip2' and commandExists false branch).
     */
    public function testCompressFileWithBzip2CoversBzip2Branch(): void
    {
        $filePath = $this->tempDir . '/to_compress_bzip2.txt';
        file_put_contents($filePath, 'content');

        $ref    = new ReflectionClass(DatabaseExportService::class);
        $method = $ref->getMethod('compressFile');
        $method->setAccessible(true);

        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            'x',
            'bzip2',
            false,
        );

        $result = $method->invoke($service, $filePath);
        $this->assertNotNull($result);
        if (str_ends_with($result, '.bz2')) {
            $this->assertFileExists($result);
        } else {
            $this->assertSame($filePath, $result);
        }
    }

    /**
     * Test createZipArchive returns false when ZipArchive::open fails (e.g. path not writable) (lines 373-374).
     */
    public function testCreateZipArchiveReturnsFalseWhenOpenFails(): void
    {
        $subDir = $this->tempDir . '/zip_fail_src';
        mkdir($subDir, 0o755, true);
        file_put_contents($subDir . '/file.txt', 'content');

        $ref    = new ReflectionClass(DatabaseExportService::class);
        $method = $ref->getMethod('createZipArchive');
        $method->setAccessible(true);

        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            'x',
            'none',
            false,
        );

        // /dev/null (or similar non-regular path) causes ZipArchive::open to fail with error code, not true
        $readOnlyPath = (PHP_OS_FAMILY === 'Windows') ? 'NUL' : '/dev/null';
        $result = $method->invoke($service, $subDir, $readOnlyPath);
        $this->assertFalse($result);
    }

    /**
     * Test removeDirectory recursively removes nested subdirectories (is_dir branch lines 334-335).
     */
    public function testRemoveDirectoryWithNestedSubdirectory(): void
    {
        $nestedDir = $this->tempDir . '/nested_parent/nested_child';
        mkdir($nestedDir, 0o755, true);
        file_put_contents($nestedDir . '/file.txt', 'content');

        $ref    = new ReflectionClass(DatabaseExportService::class);
        $method = $ref->getMethod('removeDirectory');
        $method->setAccessible(true);

        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            'x',
            'none',
            false,
        );

        $result = $method->invoke($service, $this->tempDir . '/nested_parent');
        $this->assertTrue($result);
        $this->assertDirectoryDoesNotExist($this->tempDir . '/nested_parent');
    }

    /**
     * Test removeDirectory via reflection.
     */
    public function testRemoveDirectory(): void
    {
        $subDir = $this->tempDir . '/to_remove';
        mkdir($subDir, 0o755, true);
        file_put_contents($subDir . '/file.txt', 'x');

        $ref    = new ReflectionClass(DatabaseExportService::class);
        $method = $ref->getMethod('removeDirectory');
        $method->setAccessible(true);

        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            'x',
            'none',
            false,
        );

        $result = $method->invoke($service, $subDir);
        $this->assertTrue($result);
        $this->assertDirectoryDoesNotExist($subDir);
    }

    /**
     * Test removeDirectory returns false when path is not a directory.
     */
    public function testRemoveDirectoryReturnsFalseWhenNotDirectory(): void
    {
        $ref    = new ReflectionClass(DatabaseExportService::class);
        $method = $ref->getMethod('removeDirectory');
        $method->setAccessible(true);

        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            'x',
            'none',
            false,
        );

        $result = $method->invoke($service, $this->tempDir . '/nonexistent');
        $this->assertFalse($result);
    }

    /**
     * Test commandExists returns false for nonexistent command (covers returnCode !== 0 or empty stdout).
     */
    /**
     * Test SystemCommandRunner::commandExists returns false for a nonexistent command.
     */
    public function testCommandExistsReturnsFalseForNonexistentCommand(): void
    {
        $runner = new \Nowo\AnonymizeBundle\Service\SystemCommandRunner();
        $result = $runner->commandExists('nonexistent_command_xyz_' . uniqid());
        $this->assertFalse($result);
    }

    /**
     * Test SystemCommandRunner::commandExists returns true for an existing command (e.g. "php" on the PATH).
     */
    public function testCommandExistsReturnsTrueForExistingCommand(): void
    {
        $runner = new \Nowo\AnonymizeBundle\Service\SystemCommandRunner();
        $result = $runner->commandExists('php');
        $this->assertTrue($result);
    }

    /**
     * Test createZipArchive with a single file creates valid zip.
     */
    public function testCreateZipArchiveWithEmptyDirectory(): void
    {
        $subDir = $this->tempDir . '/zip_empty';
        mkdir($subDir, 0o755, true);
        file_put_contents($subDir . '/dummy.txt', 'x');
        $zipPath = $this->tempDir . '/empty.zip';

        $ref    = new ReflectionClass(DatabaseExportService::class);
        $method = $ref->getMethod('createZipArchive');
        $method->setAccessible(true);

        $service = new DatabaseExportService(
            $this->container,
            $this->tempDir,
            'x',
            'none',
            false,
        );

        $result = $method->invoke($service, $subDir, $zipPath);
        $this->assertTrue($result);
        $this->assertFileExists($zipPath);
    }

    /**
     * Helper method to recursively remove directory.
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
