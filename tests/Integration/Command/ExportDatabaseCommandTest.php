<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nowo\AnonymizeBundle\Command\ExportDatabaseCommand;
use Nowo\AnonymizeBundle\Enum\SymfonyService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Test case for ExportDatabaseCommand.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class ExportDatabaseCommandTest extends TestCase
{
    private ContainerInterface $container;
    private ExportDatabaseCommand $command;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->command   = new ExportDatabaseCommand($this->container);
    }

    /**
     * Test that command can be instantiated.
     */
    public function testCommandInstantiation(): void
    {
        $this->assertInstanceOf(ExportDatabaseCommand::class, $this->command);
        $this->assertEquals('nowo:anonymize:export-db', $this->command->getName());
    }

    /**
     * Test that command configuration is correct.
     */
    public function testCommandConfiguration(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('connection'));
        $this->assertTrue($definition->hasOption('output-dir'));
        $this->assertTrue($definition->hasOption('filename-pattern'));
        $this->assertTrue($definition->hasOption('compression'));
        $this->assertTrue($definition->hasOption('no-gitignore'));

        $compressionOption = $definition->getOption('compression');
        $this->assertEquals('gzip', $compressionOption->getDefault());
    }

    /**
     * Test that command returns failure when environment protection fails (e.g. prod).
     */
    public function testExecuteReturnsFailureWhenEnvironmentProtectionFails(): void
    {
        $parameterBag = new ParameterBag([
            'kernel.environment' => 'prod',
            'kernel.debug'       => false,
            'kernel.project_dir' => sys_get_temp_dir(),
        ]);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn (string $id) => $id === 'parameter_bag');
        $container->method('get')->willReturnCallback(static fn (string $id) => $id === 'parameter_bag' ? $parameterBag : null);

        $command = new ExportDatabaseCommand($container);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Environment protection checks failed', $output->fetch());
    }

    /**
     * Test that command returns failure when no managers found.
     */
    public function testExecuteReturnsFailureWhenNoManagersFound(): void
    {
        $parameterBag = new ParameterBag([
            'kernel.environment' => 'dev',
            'kernel.debug'       => true,
            'kernel.project_dir' => sys_get_temp_dir(),
        ]);
        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn([]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static function (string $id) {
            return $id === 'parameter_bag' || $id === SymfonyService::DOCTRINE;
        });
        $container->method('get')->willReturnCallback(static function (string $id) use ($parameterBag, $doctrine) {
            return $id === 'parameter_bag' ? $parameterBag : ($id === SymfonyService::DOCTRINE ? $doctrine : null);
        });

        $command = new ExportDatabaseCommand($container);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('No entity managers found', $output->fetch());
    }

    /**
     * Test that command returns failure when compression format is invalid.
     */
    public function testExecuteReturnsFailureWhenCompressionInvalid(): void
    {
        $parameterBag = new ParameterBag([
            'kernel.environment' => 'dev',
            'kernel.debug'       => true,
            'kernel.project_dir' => sys_get_temp_dir(),
        ]);
        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static function (string $id) {
            return $id === 'parameter_bag' || $id === SymfonyService::DOCTRINE;
        });
        $container->method('get')->willReturnCallback(static function (string $id) use ($parameterBag, $doctrine) {
            return $id === 'parameter_bag' ? $parameterBag : ($id === SymfonyService::DOCTRINE ? $doctrine : null);
        });

        $command = new ExportDatabaseCommand($container);
        $input   = new ArrayInput(['--compression' => 'invalid']);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Invalid compression format', $output->fetch());
    }

    /**
     * Test that command handles getParameterBag correctly.
     */
    public function testGetParameterBagReturnsParameterBag(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->container->method('has')
            ->with('parameter_bag')
            ->willReturn(true);
        $this->container->method('get')
            ->with('parameter_bag')
            ->willReturn($parameterBag);

        // Use reflection to test private method
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('getParameterBag');
        $method->setAccessible(true);

        $result = $method->invoke($this->command);

        $this->assertInstanceOf(ParameterBagInterface::class, $result);
    }

    /**
     * Test that command handles getParameterBag fallback when service not available.
     */
    public function testGetParameterBagHandlesMissingService(): void
    {
        $this->container->method('has')
            ->willReturn(false);

        // Use reflection to test private method
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('getParameterBag');
        $method->setAccessible(true);

        // Should return a ParameterBagInterface wrapper when parameter_bag is not available
        $result = $method->invoke($this->command);
        $this->assertInstanceOf(ParameterBagInterface::class, $result);
    }

    /**
     * Test that getParameterBag falls back to KernelParameterBagAdapter when container has parameter_bag but get() throws (line 314).
     */
    public function testGetParameterBagFallsBackToAdapterWhenGetParameterBagThrows(): void
    {
        $this->container->method('has')
            ->with('parameter_bag')
            ->willReturn(true);
        $this->container->method('get')
            ->with('parameter_bag')
            ->willThrowException(new Exception('parameter_bag service broken'));

        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('getParameterBag');
        $method->setAccessible(true);

        $result = $method->invoke($this->command);

        $this->assertInstanceOf(ParameterBagInterface::class, $result);
        $this->assertInstanceOf(\Nowo\AnonymizeBundle\Internal\KernelParameterBagAdapter::class, $result);
    }

    /**
     * Test that command handles formatBytes correctly.
     */
    public function testFormatBytesFormatsCorrectly(): void
    {
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, 1024);
        $this->assertIsString($result);
        $this->assertStringContainsString('KB', $result);

        $result = $method->invoke($this->command, 1024 * 1024);
        $this->assertIsString($result);
        $this->assertStringContainsString('MB', $result);

        $result = $method->invoke($this->command, 1024 * 1024 * 1024);
        $this->assertIsString($result);
        $this->assertStringContainsString('GB', $result);
    }

    /**
     * Test that command handles formatBytes with zero bytes.
     */
    public function testFormatBytesHandlesZeroBytes(): void
    {
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, 0);
        $this->assertIsString($result);
        $this->assertStringContainsString('0', $result);
    }

    /**
     * Test that command handles formatBytes with very large numbers.
     */
    public function testFormatBytesHandlesLargeNumbers(): void
    {
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('formatBytes');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, 1024 * 1024 * 1024 * 1024);
        $this->assertIsString($result);
        $this->assertStringContainsString('TB', $result);
    }

    /**
     * Test that when --output-dir is not passed, command uses nowo_anonymize.export.output_dir from parameter bag (lines 126-128).
     */
    public function testExecuteUsesOutputDirFromParameterBagWhenOptionNotPassed(): void
    {
        $customExportDir = sys_get_temp_dir() . '/custom_export_dir_' . uniqid();
        $parameterBag    = new ParameterBag([
            'kernel.environment'               => 'dev',
            'kernel.debug'                     => true,
            'kernel.project_dir'               => sys_get_temp_dir(),
            'nowo_anonymize.export.output_dir' => $customExportDir,
        ]);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getParams')->willReturn([]);
        $connection->method('getDriver')->willReturn($this->createMock(Driver::class));
        $connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(AbstractPlatform::class));
        $em->method('getConnection')->willReturn($connection);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder($parameterBag);
        $container->set('parameter_bag', $parameterBag);
        $container->set(SymfonyService::DOCTRINE, $doctrine);

        $command = new ExportDatabaseCommand($container);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $command->run($input, $output);
        $content = $output->fetch();

        $this->assertStringContainsString($customExportDir, $content, 'Output dir from parameter bag should appear in output');
    }

    /**
     * Test that when --filename-pattern and --no-gitignore are not passed,
     * command uses nowo_anonymize.export.filename_pattern and .auto_gitignore from parameter bag (lines 132-135, 146-147).
     * Note: --compression has default 'gzip', so the parameter bag branch for compression (138-141) is unreachable unless the option had no default.
     */
    public function testExecuteUsesFilenamePatternAndAutoGitignoreFromParameterBagWhenOptionsNotPassed(): void
    {
        $customExportDir   = sys_get_temp_dir() . '/export_cfg_' . uniqid();
        $customFilenamePat = 'backup_{connection}_{date}.sql';
        $parameterBag      = new ParameterBag([
            'kernel.environment'                     => 'dev',
            'kernel.debug'                           => true,
            'kernel.project_dir'                     => sys_get_temp_dir(),
            'nowo_anonymize.export.output_dir'       => $customExportDir,
            'nowo_anonymize.export.filename_pattern' => $customFilenamePat,
            'nowo_anonymize.export.auto_gitignore'   => false,
        ]);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getParams')->willReturn([]);
        $connection->method('getDriver')->willReturn($this->createMock(Driver::class));
        $connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(AbstractPlatform::class));
        $em->method('getConnection')->willReturn($connection);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder($parameterBag);
        $container->set('parameter_bag', $parameterBag);
        $container->set(SymfonyService::DOCTRINE, $doctrine);

        $command = new ExportDatabaseCommand($container);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $command->run($input, $output);
        $content = $output->fetch();

        $this->assertStringContainsString($customFilenamePat, $content, 'Filename pattern from parameter bag should appear in configuration table');
        $this->assertStringContainsString('Auto .gitignore', $content);
        $this->assertMatchesRegularExpression('/Auto \.gitignore[\s\S]*?No/', $content, 'Auto .gitignore should be No when nowo_anonymize.export.auto_gitignore is false');
    }

    /**
     * Test that command resolves %kernel.project_dir% in output-dir and shows configuration table.
     */
    public function testExecuteResolvesKernelProjectDirInOutputDir(): void
    {
        $projectDir   = sys_get_temp_dir() . '/export_test_' . uniqid();
        $parameterBag = new ParameterBag([
            'kernel.environment' => 'dev',
            'kernel.debug'       => true,
            'kernel.project_dir' => $projectDir,
        ]);
        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getParams')->willReturn([]);
        $connection->method('getDriver')->willReturn($this->createMock(Driver::class));
        $connection->method('getDatabasePlatform')
            ->willReturn($this->createMock(AbstractPlatform::class));
        $em->method('getConnection')->willReturn($connection);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder($parameterBag);
        $container->set('parameter_bag', $parameterBag);
        $container->set(SymfonyService::DOCTRINE, $doctrine);

        $command = new ExportDatabaseCommand($container);
        $input   = new ArrayInput(['--output-dir' => '%kernel.project_dir%/my_exports']);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $content  = $output->fetch();

        $expectedResolvedPath = $projectDir . '/my_exports';
        $this->assertStringContainsString($expectedResolvedPath, $content, 'Resolved output dir should appear in output');
        $this->assertStringContainsString('Output Directory', $content);
        $this->assertStringContainsString('Database Export', $content);
        $this->assertStringContainsString('Configuration', $content);
        // Exit code may be 0 (export succeeded) or 1 (export failed e.g. no mysqldump); path resolution is what we test
        $this->assertContains($exitCode, [0, 1]);
    }

    /**
     * Test that when MONGODB_URL is set and connection is mongodb, command parses URL and calls exportMongoDB (covers lines 220-232).
     * mongodump is not available so export fails, but parse_url and exportMongoDB path are covered.
     */
    public function testExecuteWithMongoConnectionAndMongoUrlParsesUrlAndAttemptsExport(): void
    {
        $backup              = $_ENV['MONGODB_URL'] ?? null;
        $_ENV['MONGODB_URL'] = 'mongodb://myhost:27018/mydb?authSource=admin';

        try {
            $parameterBag = new ParameterBag([
                'kernel.environment' => 'dev',
                'kernel.debug'       => true,
                'kernel.project_dir' => sys_get_temp_dir(),
            ]);
            $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
            $doctrine->method('getManagerNames')->willReturn([]);

            $container = $this->createMock(ContainerInterface::class);
            $container->method('has')->willReturnCallback(static fn (string $id) => $id === 'parameter_bag' || $id === SymfonyService::DOCTRINE);
            $container->method('get')->willReturnCallback(static function (string $id) use ($parameterBag, $doctrine) {
                return $id === 'parameter_bag' ? $parameterBag : ($id === SymfonyService::DOCTRINE ? $doctrine : null);
            });

            $command = new ExportDatabaseCommand($container);
            $input   = new ArrayInput(['--connection' => ['mongodb'], '--no-gitignore' => true]);
            $output  = new BufferedOutput();

            $exitCode = $command->run($input, $output);
            $content  = $output->fetch();

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('Exporting', $content);
            $this->assertStringContainsString('mongodb', $content);
            $this->assertStringContainsString('Failed to export', $content);
        } finally {
            if ($backup !== null) {
                $_ENV['MONGODB_URL'] = $backup;
            } else {
                unset($_ENV['MONGODB_URL']);
            }
        }
    }

    /**
     * Test that when connection is mongodb, MONGODB_URL is not set, and Doctrine has a mongodb manager,
     * the command gets host/port/database from the connection and calls exportMongoDB (covers lines 235-243).
     * mongodump is not available so export fails; we assert the warning and failure count.
     */
    public function testExecuteWithMongoConnectionNoUrlGetsParamsFromDoctrineAndAttemptsExport(): void
    {
        $backup = $_ENV['MONGODB_URL'] ?? null;
        if (isset($_ENV['MONGODB_URL'])) {
            unset($_ENV['MONGODB_URL']);
        }

        try {
            $parameterBag = new ParameterBag([
                'kernel.environment' => 'dev',
                'kernel.debug'       => true,
                'kernel.project_dir' => sys_get_temp_dir(),
            ]);

            $connection = $this->createMock(Connection::class);
            $connection->method('getParams')->willReturn(['host' => 'mongo.local', 'port' => 27018]);
            $connection->method('getDatabase')->willReturn('mydb_from_doctrine');

            $em = $this->createMock(EntityManagerInterface::class);
            $em->method('getConnection')->willReturn($connection);

            $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
            $doctrine->method('getManagerNames')->willReturn(['mongodb' => 'doctrine_mongodb.odm.mongodb_document_manager']);
            $doctrine->method('getManager')->with('mongodb')->willReturn($em);

            $container = $this->createMock(ContainerInterface::class);
            $container->method('has')->willReturnCallback(static fn (string $id) => $id === 'parameter_bag' || $id === SymfonyService::DOCTRINE);
            $container->method('get')->willReturnCallback(static function (string $id) use ($parameterBag, $doctrine) {
                return $id === 'parameter_bag' ? $parameterBag : ($id === SymfonyService::DOCTRINE ? $doctrine : null);
            });

            $command = new ExportDatabaseCommand($container);
            $input   = new ArrayInput(['--connection' => ['mongodb'], '--no-gitignore' => true]);
            $output  = new BufferedOutput();

            $exitCode = $command->run($input, $output);
            $content  = $output->fetch();

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('Exporting', $content);
            $this->assertStringContainsString('mongodb', $content);
            $this->assertStringContainsString('Failed to export', $content);
        } finally {
            if ($backup !== null) {
                $_ENV['MONGODB_URL'] = $backup;
            } else {
                unset($_ENV['MONGODB_URL']);
            }
        }
    }

    /**
     * Test that when --connection mongodb is requested but MONGODB_URL is not set and Doctrine has no mongodb manager,
     * the command outputs a message about MONGODB_URL and counts a failure.
     */
    public function testExecuteWithMongoConnectionRequestedWhenNoMongoUrl(): void
    {
        $backup = $_ENV['MONGODB_URL'] ?? null;
        if (isset($_ENV['MONGODB_URL'])) {
            unset($_ENV['MONGODB_URL']);
        }

        try {
            $parameterBag = new ParameterBag([
                'kernel.environment' => 'dev',
                'kernel.debug'       => true,
                'kernel.project_dir' => sys_get_temp_dir(),
            ]);
            $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
            $doctrine->method('getManagerNames')->willReturn([]);
            $doctrine->method('getManager')->with('mongodb')->willThrowException(new Exception('No manager for mongodb'));

            $container = $this->createMock(ContainerInterface::class);
            $container->method('has')->willReturnCallback(static function (string $id) {
                return $id === 'parameter_bag' || $id === SymfonyService::DOCTRINE;
            });
            $container->method('get')->willReturnCallback(static function (string $id) use ($parameterBag, $doctrine) {
                return $id === 'parameter_bag' ? $parameterBag : ($id === SymfonyService::DOCTRINE ? $doctrine : null);
            });

            $command = new ExportDatabaseCommand($container);
            $input   = new ArrayInput(['--connection' => ['mongodb']]);
            $output  = new BufferedOutput();

            $exitCode = $command->run($input, $output);
            $content  = $output->fetch();

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('MONGODB_URL', $content);
            $this->assertStringContainsString('1 export(s) failed', $content);
        } finally {
            if ($backup !== null) {
                $_ENV['MONGODB_URL'] = $backup;
            }
        }
    }

    /**
     * Test that when getManager throws, command catches exception, shows "Error exporting" and increments failure count (lines 271-273).
     */
    public function testExecuteShowsErrorExportingWhenGetManagerThrows(): void
    {
        $parameterBag = new ParameterBag([
            'kernel.environment' => 'dev',
            'kernel.debug'       => true,
            'kernel.project_dir' => sys_get_temp_dir(),
        ]);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willThrowException(new Exception('Manager failed'));

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn (string $id) => $id === 'parameter_bag' || $id === SymfonyService::DOCTRINE);
        $container->method('get')->willReturnCallback(static function (string $id) use ($parameterBag, $doctrine) {
            return $id === 'parameter_bag' ? $parameterBag : ($id === SymfonyService::DOCTRINE ? $doctrine : null);
        });

        $command = new ExportDatabaseCommand($container);
        $input   = new ArrayInput(['--no-gitignore' => true]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $content  = $output->fetch();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Error exporting', $content);
        $this->assertStringContainsString('Manager failed', $content);
        $this->assertStringContainsString('1 export(s) failed', $content);
    }

    /**
     * Test that when export returns null (e.g. unsupported driver), command shows "Failed to export" and returns failure.
     */
    public function testExecuteShowsFailedToExportWhenExportReturnsNull(): void
    {
        $parameterBag = new ParameterBag([
            'kernel.environment' => 'dev',
            'kernel.debug'       => true,
            'kernel.project_dir' => sys_get_temp_dir(),
        ]);

        $connection = $this->createMock(Connection::class);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_unknown']);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDriver')->willReturn($this->createMock(Driver::class));
        $connection->method('getDatabasePlatform')->willThrowException(new Exception('no platform'));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn (string $id) => $id === 'parameter_bag' || $id === SymfonyService::DOCTRINE);
        $container->method('get')->willReturnCallback(static function (string $id) use ($parameterBag, $doctrine) {
            return $id === 'parameter_bag' ? $parameterBag : ($id === SymfonyService::DOCTRINE ? $doctrine : null);
        });

        $command = new ExportDatabaseCommand($container);
        $input   = new ArrayInput(['--no-gitignore' => true]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $content  = $output->fetch();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Failed to export', $content);
        $this->assertStringContainsString('1 export(s) failed', $content);
    }

    /**
     * Test that getProjectDirFromContainer returns getcwd() when container has no hasParameter/getParameter (e.g. plain ContainerInterface).
     */
    public function testGetProjectDirFromContainerReturnsCwdWhenNoParameter(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        // ContainerInterface has only has() and get(); no hasParameter → method_exists is false → fallback to getcwd()

        $command    = new ExportDatabaseCommand($container);
        $reflection = new ReflectionClass($command);
        $method     = $reflection->getMethod('getProjectDirFromContainer');
        $method->setAccessible(true);

        $result = $method->invoke($command);

        $this->assertNotNull($result);
        $this->assertNotEmpty($result);
        $this->assertSame(getcwd(), $result);
    }

    /**
     * Test that when export succeeds (e.g. SQLite), command shows "Exported to", formatBytes and success summary (covers lines 261-266, 294-296).
     */
    public function testExecuteShowsExportedToAndSuccessWhenExportSucceeds(): void
    {
        $outputDir  = sys_get_temp_dir() . '/export_success_' . uniqid();
        $testDbPath = sys_get_temp_dir() . '/source_export_cmd_' . uniqid() . '.db';
        file_put_contents($testDbPath, 'SQLite content for export command test');

        $parameterBag = new ParameterBag([
            'kernel.environment' => 'dev',
            'kernel.debug'       => true,
            'kernel.project_dir' => sys_get_temp_dir(),
        ]);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite', 'path' => $testDbPath]);
        $connection->method('getDriver')->willReturn($this->createMock(Driver::class));
        $connection->method('getDatabasePlatform')->willReturn($this->createMock(AbstractPlatform::class));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn (string $id) => $id === 'parameter_bag' || $id === SymfonyService::DOCTRINE);
        $container->method('get')->willReturnCallback(static function (string $id) use ($parameterBag, $doctrine) {
            return $id === 'parameter_bag' ? $parameterBag : ($id === SymfonyService::DOCTRINE ? $doctrine : null);
        });

        $command = new ExportDatabaseCommand($container);
        $input   = new ArrayInput([
            '--output-dir'   => $outputDir,
            '--compression'  => 'none',
            '--no-gitignore' => true,
        ]);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $content  = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Exported to', $content);
        $this->assertStringContainsString('Successfully exported 1 database(s)', $content);
        $this->assertMatchesRegularExpression('/\d+\.?\d*\s*(B|KB|MB)/', $content, 'formatBytes output (size) should appear');

        if (is_file($testDbPath)) {
            unlink($testDbPath);
        }
        if (is_dir($outputDir)) {
            array_map('unlink', glob($outputDir . '/*') ?: []);
            rmdir($outputDir);
        }
    }

    /**
     * Test that when export succeeds with autoGitignore enabled, command shows note ".gitignore has been updated" (lines 288-290).
     */
    public function testExecuteShowsGitignoreNoteWhenExportSucceedsAndAutoGitignoreEnabled(): void
    {
        $projectDir = sys_get_temp_dir() . '/export_gitignore_' . uniqid();
        mkdir($projectDir, 0o755, true);
        $outputDir  = $projectDir . '/var/exports';
        $testDbPath = sys_get_temp_dir() . '/source_gitignore_note_' . uniqid() . '.db';
        file_put_contents($testDbPath, 'SQLite for gitignore note test');

        $parameterBag = new ParameterBag([
            'kernel.environment' => 'dev',
            'kernel.debug'       => true,
            'kernel.project_dir' => $projectDir,
        ]);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getParams')->willReturn(['driver' => 'pdo_sqlite', 'path' => $testDbPath]);
        $connection->method('getDriver')->willReturn($this->createMock(Driver::class));
        $connection->method('getDatabasePlatform')->willReturn($this->createMock(AbstractPlatform::class));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn (string $id) => $id === 'parameter_bag' || $id === SymfonyService::DOCTRINE);
        $container->method('get')->willReturnCallback(static function (string $id) use ($parameterBag, $doctrine) {
            return $id === 'parameter_bag' ? $parameterBag : ($id === SymfonyService::DOCTRINE ? $doctrine : null);
        });

        $command = new ExportDatabaseCommand($container);
        $input   = new ArrayInput([
            '--output-dir'  => $outputDir,
            '--compression' => 'none',
        ]);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $content  = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('.gitignore has been updated to exclude', $content);
        $this->assertStringContainsString('export directory', $content);

        if (is_file($testDbPath)) {
            unlink($testDbPath);
        }
        if (is_dir($outputDir)) {
            array_map('unlink', glob($outputDir . '/*') ?: []);
            rmdir($outputDir);
        }
        if (is_file($projectDir . '/.gitignore')) {
            unlink($projectDir . '/.gitignore');
        }
        if (is_dir($projectDir)) {
            rmdir($projectDir);
        }
    }
}
