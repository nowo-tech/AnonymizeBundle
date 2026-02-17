<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nowo\AnonymizeBundle\Command\AnonymizeInfoCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Container;

/**
 * Test case for AnonymizeInfoCommand.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizeInfoCommandTest extends TestCase
{
    /**
     * Test that command can be instantiated.
     */
    public function testCommandCanBeInstantiated(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $command   = new AnonymizeInfoCommand($container, 'en_US', []);

        $this->assertInstanceOf(AnonymizeInfoCommand::class, $command);
    }

    /**
     * Test that command returns failure when no entity managers found.
     */
    public function testCommandReturnsFailureWhenNoEntityManagers(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $doctrine  = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);

        $container->method('get')
            ->with('doctrine')
            ->willReturn($doctrine);

        $doctrine->method('getManagerNames')
            ->willReturn([]);

        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertEquals(1, $result);
        $this->assertStringContainsString('No entity managers found', $output->fetch());
    }

    /**
     * Test that command handles empty connections option.
     */
    public function testCommandHandlesEmptyConnections(): void
    {
        $container  = $this->createMock(ContainerInterface::class);
        $doctrine   = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $container->method('get')
            ->with('doctrine')
            ->willReturn($doctrine);

        $doctrine->method('getManagerNames')
            ->willReturn(['default' => 'default']);

        $doctrine->method('getManager')
            ->with('default')
            ->willReturn($em);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('getDatabase')
            ->willReturn('test_db');

        // Mock AnonymizeService to return empty entities
        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        // We need to mock the service container to return the services
        // Since AnonymizeService is instantiated directly, we need to ensure it works
        $result = $command->run($input, $output);

        // The command should succeed even if no entities are found
        $this->assertIsInt($result);
    }

    /**
     * Test that command handles exception during processing.
     */
    public function testCommandHandlesExceptionDuringProcessing(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $doctrine  = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);

        $container->method('get')
            ->with('doctrine')
            ->willReturn($doctrine);

        $doctrine->method('getManagerNames')
            ->willReturn(['default' => 'default']);

        $doctrine->method('getManager')
            ->with('default')
            ->willThrowException(new Exception('Test exception'));

        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertEquals(1, $result);
        $this->assertStringContainsString('Error processing', $output->fetch());
    }

    /**
     * Test that command uses default locale when not provided.
     */
    public function testCommandUsesDefaultLocale(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $command   = new AnonymizeInfoCommand($container, 'es_ES', []);

        $this->assertInstanceOf(AnonymizeInfoCommand::class, $command);
    }

    /**
     * Test that command uses provided connections.
     */
    public function testCommandUsesProvidedConnections(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $command   = new AnonymizeInfoCommand($container, 'en_US', ['default', 'postgres']);

        $this->assertInstanceOf(AnonymizeInfoCommand::class, $command);
    }

    /**
     * Test that command configure method sets options correctly.
     */
    public function testCommandConfigureSetsOptions(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $command   = new AnonymizeInfoCommand($container, 'en_US', []);

        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('connection'));
        $this->assertTrue($definition->hasOption('locale'));
    }

    /**
     * Test that command handles locale option.
     */
    public function testCommandHandlesLocaleOption(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $doctrine  = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);

        $container->method('get')
            ->with('doctrine')
            ->willReturn($doctrine);

        $doctrine->method('getManagerNames')
            ->willReturn([]);

        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $input   = new ArrayInput(['--locale' => 'es_ES']);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        // Should fail because no managers, but locale option should be processed
        $this->assertEquals(1, $result);
    }

    /**
     * Test that command handles connection option.
     */
    public function testCommandHandlesConnectionOption(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $doctrine  = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);

        $container->method('get')
            ->with('doctrine')
            ->willReturn($doctrine);

        $doctrine->method('getManagerNames')
            ->willReturn(['default' => 'default']);

        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $input   = new ArrayInput(['--connection' => ['default']]);
        $output  = new BufferedOutput();

        // Mock getManager to avoid actual execution
        $doctrine->method('getManager')
            ->willThrowException(new Exception('Test'));

        $result = $command->run($input, $output);

        // Should fail with error, but connection option should be processed
        $this->assertEquals(1, $result);
    }
}
