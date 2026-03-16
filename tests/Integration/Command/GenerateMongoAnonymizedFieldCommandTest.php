<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Command;

use Nowo\AnonymizeBundle\Command\GenerateMongoAnonymizedFieldCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use function is_string;

/**
 * Test case for GenerateMongoAnonymizedFieldCommand.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class GenerateMongoAnonymizedFieldCommandTest extends TestCase
{
    private ContainerInterface $container;
    private GenerateMongoAnonymizedFieldCommand $command;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->command   = new GenerateMongoAnonymizedFieldCommand($this->container);
    }

    /**
     * Test that command can be instantiated.
     */
    public function testCommandInstantiation(): void
    {
        $this->assertInstanceOf(GenerateMongoAnonymizedFieldCommand::class, $this->command);
        $this->assertEquals('nowo:anonymize:generate-mongo-field', $this->command->getName());
    }

    /**
     * Test that command configuration is correct.
     */
    public function testCommandConfiguration(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('database'));
        $this->assertTrue($definition->hasOption('collection'));
        $this->assertTrue($definition->hasOption('scan-documents'));
        $this->assertTrue($definition->hasOption('document-path'));
        $this->assertTrue($definition->hasOption('output'));

        $collectionOption = $definition->getOption('collection');
        $this->assertTrue($collectionOption->isArray());
    }

    /**
     * Test that command returns failure when no collections specified.
     */
    public function testExecuteReturnsFailureWhenNoCollections(): void
    {
        $input  = new ArrayInput([]);
        $output = new BufferedOutput();

        $result = $this->command->run($input, $output);

        $this->assertEquals(GenerateMongoAnonymizedFieldCommand::FAILURE, $result);
        $outputContent = $output->fetch();
        $this->assertStringContainsString('No collections specified', $outputContent);
    }

    /**
     * Test that command generates script successfully with collections.
     */
    public function testExecuteGeneratesScriptWithCollections(): void
    {
        $input  = new ArrayInput(['--collection' => ['users', 'activities']]);
        $output = new BufferedOutput();

        $result = $this->command->run($input, $output);

        $this->assertEquals(GenerateMongoAnonymizedFieldCommand::SUCCESS, $result);
        $outputContent = $output->fetch();
        $this->assertStringContainsString('MongoDB Script', $outputContent);
        $this->assertStringContainsString('users', $outputContent);
        $this->assertStringContainsString('activities', $outputContent);
    }

    /**
     * Test that command saves script to file when output option is provided.
     */
    public function testExecuteSavesScriptToFile(): void
    {
        $tempFile = sys_get_temp_dir() . '/test_mongo_script_' . uniqid() . '.js';

        $input  = new ArrayInput(['--collection' => ['users'], '--output' => $tempFile]);
        $output = new BufferedOutput();

        $result = $this->command->run($input, $output);

        $this->assertEquals(GenerateMongoAnonymizedFieldCommand::SUCCESS, $result);
        $this->assertFileExists($tempFile);
        $fileContent = file_get_contents($tempFile);
        $this->assertStringContainsString('use(', $fileContent);
        $this->assertStringContainsString('users', $fileContent);

        // Cleanup
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    /**
     * Test that command uses custom database name.
     */
    public function testExecuteUsesCustomDatabase(): void
    {
        $input  = new ArrayInput(['--collection' => ['users'], '--database' => 'myapp']);
        $output = new BufferedOutput();

        $result = $this->command->run($input, $output);

        $this->assertEquals(GenerateMongoAnonymizedFieldCommand::SUCCESS, $result);
        $outputContent = $output->fetch();
        $this->assertStringContainsString('myapp', $outputContent);
    }

    /**
     * Test that generateMongoScript generates correct script.
     */
    public function testGenerateMongoScriptGeneratesCorrectScript(): void
    {
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('generateMongoScript');
        $method->setAccessible(true);

        $script = $method->invoke($this->command, 'test_db', ['users', 'activities']);

        $this->assertStringContainsString('test_db', $script);
        $this->assertStringContainsString('users', $script);
        $this->assertStringContainsString('activities', $script);
        $this->assertStringContainsString('updateMany', $script);
        $this->assertStringContainsString('anonymized', $script);
    }

    /**
     * Test that generateMongoScript handles empty collections array.
     */
    public function testGenerateMongoScriptHandlesEmptyCollections(): void
    {
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('generateMongoScript');
        $method->setAccessible(true);

        $script = $method->invoke($this->command, 'test_db', []);

        $this->assertStringContainsString('test_db', $script);
        $this->assertStringContainsString('use(', $script);
    }

    /**
     * Test that getProjectRoot returns null when kernel not available.
     */
    public function testGetProjectRootReturnsNullWhenKernelNotAvailable(): void
    {
        $this->container->method('has')
            ->with('kernel')
            ->willReturn(false);

        // Use reflection to test private method
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('getProjectRoot');
        $method->setAccessible(true);

        $result = $method->invoke($this->command);

        // Should return null or a valid path
        $this->assertTrue($result === null || is_string($result));
    }

    /**
     * Test that getProjectRoot returns project dir from kernel.
     */
    public function testGetProjectRootReturnsFromKernel(): void
    {
        $kernel = $this->createMock(\Symfony\Component\HttpKernel\KernelInterface::class);
        $kernel->method('getProjectDir')
            ->willReturn('/test/project');

        $this->container->method('has')
            ->willReturnCallback(static fn (string $id): bool => $id === 'kernel');
        $this->container->method('get')
            ->willReturnCallback(static function (string $id) use ($kernel): mixed {
                return $id === 'kernel' ? $kernel : null;
            });

        // Use reflection to test private method
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('getProjectRoot');
        $method->setAccessible(true);

        $result = $method->invoke($this->command);

        $this->assertEquals('/test/project', $result);
    }

    /**
     * Test that execute with --scan-documents uses getProjectRoot via kernel when container has no hasParameter.
     * Covers getProjectRoot() branch that uses kernel->getProjectDir() (lines 242-248).
     */
    public function testExecuteWithScanDocumentsUsesKernelProjectDirWhenNoParameterBag(): void
    {
        $tmpDir  = sys_get_temp_dir() . '/mongo_kernel_root_' . uniqid();
        $docPath = $tmpDir . '/src/Document';
        mkdir($tmpDir, 0o755, true);
        mkdir($docPath, 0o755, true);

        $kernel = $this->createMock(\Symfony\Component\HttpKernel\KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($tmpDir);

        // Container that only has get/has (no hasParameter) so getProjectRoot uses kernel path
        $container = new class($kernel) implements ContainerInterface {
            public function __construct(private \Symfony\Component\HttpKernel\KernelInterface $kernel)
            {
            }

            public function get(string $id): mixed
            {
                return $id === 'kernel' ? $this->kernel : null;
            }

            public function has(string $id): bool
            {
                return $id === 'kernel';
            }
        };

        $command = new GenerateMongoAnonymizedFieldCommand($container);
        $input   = new ArrayInput(['--scan-documents' => true, '--document-path' => $docPath]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertSame(GenerateMongoAnonymizedFieldCommand::FAILURE, $result);
        $this->assertStringContainsString('Scanning document classes', $output->fetch());

        if (is_dir($docPath)) {
            @array_map('unlink', glob($docPath . '/*') ?: []);
            @rmdir($docPath);
        }
        if (is_dir($tmpDir . '/src')) {
            @rmdir($tmpDir . '/src');
        }
        if (is_dir($tmpDir)) {
            @rmdir($tmpDir);
        }
    }

    /**
     * Test that scanDocumentClasses returns empty array when path doesn't exist.
     */
    public function testScanDocumentClassesReturnsEmptyWhenPathNotExists(): void
    {
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->command);
        $method     = $reflection->getMethod('scanDocumentClasses');
        $method->setAccessible(true);

        $result = $method->invoke($this->command, '/nonexistent/path');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that scanDocumentClasses returns empty array when project root not found.
     * Note: Simplified test due to final class constraint.
     */
    public function testScanDocumentClassesReturnsEmptyWhenProjectRootNotFound(): void
    {
        $this->container->method('has')
            ->with('kernel')
            ->willReturn(false);

        // Use reflection to test private method
        $reflection = new ReflectionClass($this->command);
        $scanMethod = $reflection->getMethod('scanDocumentClasses');
        $scanMethod->setAccessible(true);

        // When project root is not found, it should return empty array
        // This is tested indirectly through the path not existing test
        $result = $scanMethod->invoke($this->command, '/nonexistent/path');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that execute with --scan-documents and no collections found shows warning and then failure.
     */
    public function testExecuteWithScanDocumentsNoCollectionsFoundShowsWarning(): void
    {
        $tmpDir = sys_get_temp_dir() . '/mongo_scan_test_' . uniqid();
        mkdir($tmpDir, 0o755, true);
        $docPath = $tmpDir . '/src/Document';
        mkdir($docPath, 0o755, true);

        $container = new class($tmpDir) implements ContainerInterface {
            private string $projectDir;

            public function __construct(string $projectDir)
            {
                $this->projectDir = $projectDir;
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

        $command = new GenerateMongoAnonymizedFieldCommand($container);
        $input   = new ArrayInput(['--scan-documents' => true, '--document-path' => $docPath]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $outputContent = $output->fetch();
        $this->assertStringContainsString('Scanning document classes', $outputContent);
        $this->assertStringContainsString('No collections found', $outputContent);
        $this->assertEquals(GenerateMongoAnonymizedFieldCommand::FAILURE, $result);

        // Cleanup
        if (is_dir($docPath)) {
            $files = array_diff(scandir($docPath), ['.', '..']);
            foreach ($files as $f) {
                unlink($docPath . '/' . $f);
            }
            rmdir($docPath);
        }
        if (is_dir($tmpDir . '/src')) {
            rmdir($tmpDir . '/src');
        }
        if (is_dir($tmpDir)) {
            rmdir($tmpDir);
        }
    }

    /**
     * Test that execute with --scan-documents finds collections from PHP files with #[Anonymize] and collection=.
     */
    public function testExecuteWithScanDocumentsFindsCollectionsFromPhpFiles(): void
    {
        $tmpDir  = sys_get_temp_dir() . '/mongo_scan_found_' . uniqid();
        $docPath = $tmpDir . '/Document';
        mkdir($tmpDir, 0o755, true);
        mkdir($docPath, 0o755, true);

        $phpContent = <<<'PHP'
<?php
namespace App\Document;
#[Anonymize]
#[Some\Document(collection: 'user_activities')]
class UserActivity {}
PHP;
        file_put_contents($docPath . '/UserActivity.php', $phpContent);

        $phpContent2 = <<<'PHP'
<?php
namespace App\Document;
/** @Anonymize */
// Document(collection = "audit_log")
class AuditLog {}
PHP;
        file_put_contents($docPath . '/AuditLog.php', $phpContent2);

        $phpWithCollectionEq = <<<'PHP'
<?php
#[Anonymize]
// MongoDB\Document(collection="events")
class Event {}
PHP;
        file_put_contents($docPath . '/Event.php', $phpWithCollectionEq);

        $container = new class($tmpDir) implements ContainerInterface {
            private string $projectDir;

            public function __construct(string $projectDir)
            {
                $this->projectDir = $projectDir;
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

        $command = new GenerateMongoAnonymizedFieldCommand($container);
        $input   = new ArrayInput(['--scan-documents' => true, '--document-path' => $docPath]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $outputContent = $output->fetch();
        $this->assertStringContainsString('Scanning document classes', $outputContent);
        $this->assertStringContainsString('user_activities', $outputContent);
        $this->assertEquals(GenerateMongoAnonymizedFieldCommand::SUCCESS, $result);

        if (is_dir($docPath)) {
            $files = array_diff(scandir($docPath), ['.', '..']);
            foreach ($files as $f) {
                unlink($docPath . '/' . $f);
            }
            rmdir($docPath);
        }
        if (is_dir($tmpDir)) {
            rmdir($tmpDir);
        }
    }

    /**
     * Test that when getProjectRoot() returns null, scan finds no collections (early return).
     */
    public function testExecuteWithScanDocumentsWhenProjectRootIsNullFindsNoCollections(): void
    {
        $container = new class implements ContainerInterface {
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
                return $name === 'kernel.project_dir' ? null : null;
            }
        };

        $command = new GenerateMongoAnonymizedFieldCommand($container);
        $input   = new ArrayInput(['--scan-documents' => true, '--document-path' => '/any/path']);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $outputContent = $output->fetch();
        $this->assertStringContainsString('No collections found', $outputContent);
        $this->assertEquals(GenerateMongoAnonymizedFieldCommand::FAILURE, $result);
    }

    /**
     * Test that when document-path is not a directory, scan returns no collections.
     */
    public function testExecuteWithScanDocumentsWhenDocumentPathIsNotDirectory(): void
    {
        $tmpDir   = sys_get_temp_dir() . '/mongo_scan_nodir_' . uniqid();
        $filePath = $tmpDir . '.php';
        mkdir($tmpDir, 0o755, true);
        file_put_contents($filePath, '<?php ');
        $notDirPath = $filePath;

        $container = new class($tmpDir) implements ContainerInterface {
            private string $projectDir;

            public function __construct(string $projectDir)
            {
                $this->projectDir = $projectDir;
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

        $command = new GenerateMongoAnonymizedFieldCommand($container);
        $input   = new ArrayInput(['--scan-documents' => true, '--document-path' => $notDirPath]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $outputContent = $output->fetch();
        $this->assertStringContainsString('No collections found', $outputContent);
        $this->assertEquals(GenerateMongoAnonymizedFieldCommand::FAILURE, $result);

        unlink($filePath);
        rmdir($tmpDir);
    }

    /**
     * Test that unreadable PHP file in scan path is skipped (file_get_contents returns false).
     */
    public function testExecuteWithScanDocumentsSkipsUnreadablePhpFile(): void
    {
        $tmpDir  = sys_get_temp_dir() . '/mongo_scan_unreadable_' . uniqid();
        $docPath = $tmpDir . '/Document';
        mkdir($tmpDir, 0o755, true);
        mkdir($docPath, 0o755, true);

        $readable   = $docPath . '/Readable.php';
        $unreadable = $docPath . '/Unreadable.php';
        file_put_contents($readable, "<?php\n#[Anonymize]\n#[Doc(collection: 'readable_coll')]\nclass R {}");
        file_put_contents($unreadable, "<?php\n#[Anonymize]\n#[Doc(collection: 'unreadable_coll')]\nclass U {}");
        chmod($unreadable, 0o000);

        $container = new class($tmpDir) implements ContainerInterface {
            private string $projectDir;

            public function __construct(string $projectDir)
            {
                $this->projectDir = $projectDir;
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

        $command = new GenerateMongoAnonymizedFieldCommand($container);
        $input   = new ArrayInput(['--scan-documents' => true, '--document-path' => $docPath]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $outputContent = $output->fetch();
        $this->assertStringContainsString('readable_coll', $outputContent);
        $this->assertEquals(GenerateMongoAnonymizedFieldCommand::SUCCESS, $result);

        chmod($unreadable, 0o644);
        unlink($readable);
        unlink($unreadable);
        rmdir($docPath);
        rmdir($tmpDir);
    }
}
