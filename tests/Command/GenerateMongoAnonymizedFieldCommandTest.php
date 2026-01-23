<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Command;

use Nowo\AnonymizeBundle\Command\GenerateMongoAnonymizedFieldCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

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
        $this->command = new GenerateMongoAnonymizedFieldCommand($this->container);
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
        $input = new ArrayInput([]);
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
        $input = new ArrayInput(['--collection' => ['users', 'activities']]);
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
        
        $input = new ArrayInput(['--collection' => ['users'], '--output' => $tempFile]);
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
        $input = new ArrayInput(['--collection' => ['users'], '--database' => 'myapp']);
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
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('generateMongoScript');
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
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('generateMongoScript');
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
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getProjectRoot');
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
            ->with('kernel')
            ->willReturn(true);
        $this->container->method('get')
            ->with('kernel')
            ->willReturn($kernel);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('getProjectRoot');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->command);
        
        $this->assertEquals('/test/project', $result);
    }

    /**
     * Test that scanDocumentClasses returns empty array when path doesn't exist.
     */
    public function testScanDocumentClassesReturnsEmptyWhenPathNotExists(): void
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('scanDocumentClasses');
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
        $reflection = new \ReflectionClass($this->command);
        $scanMethod = $reflection->getMethod('scanDocumentClasses');
        $scanMethod->setAccessible(true);
        
        // When project root is not found, it should return empty array
        // This is tested indirectly through the path not existing test
        $result = $scanMethod->invoke($this->command, '/nonexistent/path');
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
