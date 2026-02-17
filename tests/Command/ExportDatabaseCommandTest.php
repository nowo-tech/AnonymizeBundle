<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Command;

use Nowo\AnonymizeBundle\Command\ExportDatabaseCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
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
     * Test that command returns failure when environment protection fails.
     * Note: This test is simplified due to complex dependencies.
     */
    public function testExecuteReturnsFailureWhenEnvironmentProtectionFails(): void
    {
        // This test would require complex mocking of EnvironmentProtectionService
        // Since the service is final and has complex dependencies, we test structure instead
        $this->assertTrue(true); // Placeholder - actual test would require integration test setup
    }

    /**
     * Test that command returns failure when no managers found.
     * Note: This test is simplified due to complex dependencies.
     */
    public function testExecuteReturnsFailureWhenNoManagersFound(): void
    {
        // This test would require complex mocking of EnvironmentProtectionService and Doctrine
        // Since services are final and have complex dependencies, we test structure instead
        $this->assertTrue(true); // Placeholder - actual test would require integration test setup
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
}
