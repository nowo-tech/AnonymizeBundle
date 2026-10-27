<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Nowo\AnonymizeBundle\Service\CommandRunnerInterface;
use Nowo\AnonymizeBundle\Service\DatabaseExportService;
use PHPUnit\Framework\TestCase;

use function strlen;

/**
 * SEC-004 L2 / L3 / I2 / I8: owner-only export files, no partial dumps, argv execution, symlink-safe clean-up.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class DatabaseExportHardeningTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/anonymize_export_hardening_' . uniqid();
        mkdir($this->tempDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->tempDir));
    }

    public function testExportFilesAndDirectoryAreOwnerOnly(): void
    {
        $sqlite = $this->tempDir . '/source.sqlite';
        file_put_contents($sqlite, 'sqlite-bytes');
        $result = $this->service('none', $this->scriptedRunner([]))->exportConnection($this->em(['driver' => 'pdo_sqlite', 'path' => $sqlite]), 'default');

        $this->assertNotNull($result);
        $this->assertSame(0o600, fileperms($result) & 0o777);
        $this->assertSame(0o700, fileperms($this->tempDir . '/out') & 0o777);
    }

    public function testFailedPgDumpLeavesNoPartialDump(): void
    {
        $runner = $this->scriptedRunner(['pg_dump' => static function (array $argv): int {
            file_put_contents((string) self::option($argv, '--file='), 'partial dump');

            return 2;
        }]);

        $result = $this->service('none', $runner)->exportConnection($this->em(['driver' => 'pdo_pgsql', 'host' => 'db', 'user' => 'app']), 'default');

        $this->assertNull($result);
        $this->assertSame([], glob($this->tempDir . '/out/*') ?: []);
    }

    public function testFailedCompressionKeepsThePlainDumpAndRemovesThePartialArchive(): void
    {
        $runner = $this->scriptedRunner([
            'mysqldump' => static function (array $argv): int {
                file_put_contents((string) self::option($argv, '--result-file='), 'dump');

                return 0;
            },
            'gzip' => static function (array $argv): int {
                file_put_contents($argv[1] . '.gz', 'partial');

                return 1;
            },
        ]);

        $result = $this->service('gzip', $runner)->exportConnection($this->em(['driver' => 'pdo_mysql', 'host' => 'db', 'user' => 'app']), 'default');

        $this->assertSame($this->tempDir . '/out/default_test_db.sql', $result);
        $this->assertFileExists($result);
        $this->assertFileDoesNotExist($result . '.gz');
    }

    public function testFailedMongodumpRemovesTheBsonDirectory(): void
    {
        $runner = $this->scriptedRunner(['mongodump' => function (array $argv): int {
            $this->assertDirectoryExists($this->tempDir . '/out/mydb', 'pre-created owner-only');
            file_put_contents($this->tempDir . '/out/mydb/users.bson', 'personal data');

            return 1;
        }]);

        $this->assertNull($this->service('gzip', $runner)->exportMongoDB('mongodb', 'mydb'));
        $this->assertDirectoryDoesNotExist($this->tempDir . '/out/mydb');
    }

    public function testFailedMongoArchiveRemovesPartialArchiveAndBsonDirectoryWithoutFollowingSymlinks(): void
    {
        $outside = $this->tempDir . '/outside';
        mkdir($outside);
        file_put_contents($outside . '/keep.txt', 'not part of the dump');

        $runner = $this->scriptedRunner([
            'mongodump' => function (array $argv) use ($outside): int {
                $this->assertDirectoryExists($this->tempDir . '/out/mydb', 'pre-created owner-only');
                file_put_contents($this->tempDir . '/out/mydb/users.bson', 'personal data');
                symlink($outside, $this->tempDir . '/out/mydb/link');

                return 0;
            },
            'tar' => static function (array $argv): int {
                file_put_contents($argv[2], 'partial tar');

                return 2;
            },
        ]);

        $this->assertNull($this->service('gzip', $runner)->exportMongoDB('mongodb', 'mydb'));
        $this->assertDirectoryDoesNotExist($this->tempDir . '/out/mydb');
        $this->assertSame([], glob($this->tempDir . '/out/*') ?: []);
        $this->assertFileExists($outside . '/keep.txt');
    }

    public function testMongoArchiveIsOwnerOnly(): void
    {
        $runner = $this->scriptedRunner([
            'mongodump' => function (array $argv): int {
                $this->assertSame(0o700, fileperms($this->tempDir . '/out/mydb') & 0o777);
                file_put_contents($this->tempDir . '/out/mydb/users.bson', 'data');

                return 0;
            },
            'tar' => static function (array $argv): int {
                file_put_contents($argv[2], 'tar');

                return 0;
            },
        ]);

        $archive = $this->service('gzip', $runner)->exportMongoDB('mongodb', 'mydb');

        $this->assertSame($this->tempDir . '/out/mongodb_mydb.tar.gz', $archive);
        $this->assertSame(0o600, fileperms($archive) & 0o777);
    }

    public function testMongoDatabaseNameMustNotBeAPath(): void
    {
        $runner = $this->scriptedRunner(['mongodump' => static fn (): int => 0]);

        $this->assertNull($this->service('gzip', $runner)->exportMongoDB('mongodb', '../outside'));
        $this->assertSame([], $runner->calls);
    }

    public function testDumpToolSucceedingWithoutOutputIsAFailure(): void
    {
        $runner = $this->scriptedRunner(['mysqldump' => static fn (): int => 0]);

        $this->assertNull($this->service('none', $runner)->exportConnection($this->em(['driver' => 'pdo_mysql', 'host' => 'db']), 'default'));
        $this->assertSame([], glob($this->tempDir . '/out/*') ?: []);
    }

    public function testArgvRunnerGetsSeparateArgumentsAndTheCredentialFileInTheEnvironment(): void
    {
        $runner = $this->scriptedRunner(['pg_dump' => static function (array $argv, array $env): int {
            file_put_contents((string) self::option($argv, '--file='), 'dump');

            return 0;
        }]);

        $this->service('none', $runner)->exportConnection($this->em(['driver' => 'pdo_pgsql', 'host' => "db'; rm -rf /", 'user' => 'app', 'password' => 'not-a-real-password']), 'default');

        [$argv, $env] = $runner->calls[0];
        $this->assertSame('pg_dump', $argv[0]);
        $this->assertContains("--host=db'; rm -rf /", $argv, 'values are passed verbatim, never through a shell');
        $this->assertArrayHasKey('PGPASSFILE', $env);
        $this->assertFileDoesNotExist($env['PGPASSFILE']);
        $this->assertStringNotContainsString('not-a-real-password', implode(' ', $argv));
    }

    /**
     * @param list<string> $argv
     */
    private static function option(array $argv, string $prefix): ?string
    {
        foreach ($argv as $argument) {
            if (str_starts_with($argument, $prefix)) {
                return substr($argument, strlen($prefix));
            }
        }

        return null;
    }

    /**
     * @param array<string, callable(list<string>, array<string, string>): int> $scripts
     */
    private function scriptedRunner(array $scripts): ScriptedArgvCommandRunner
    {
        return new ScriptedArgvCommandRunner($scripts);
    }

    private function service(string $compression, CommandRunnerInterface $runner): DatabaseExportService
    {
        return new DatabaseExportService($this->tempDir, $this->tempDir . '/out', '{connection}_{database}.{format}', $compression, false, $runner);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function em(array $params): EntityManagerInterface
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $em->method('getConnection')->willReturn($connection);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($this->getMockBuilder(Driver::class)->getMock());
        $connection->method('getDatabasePlatform')->willReturn($this->createMock(AbstractPlatform::class));
        $connection->method('getParams')->willReturn($params);

        return $em;
    }
}
