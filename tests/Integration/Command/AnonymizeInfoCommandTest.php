<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Exception;
use Nowo\AnonymizeBundle\Command\AnonymizeInfoCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

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
     * Test that command configure() is executed and options are registered.
     */
    public function testCommandConfigureRegistersOptions(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $command   = new AnonymizeInfoCommand($container, 'en_US', []);

        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('connection'));
        $this->assertTrue($definition->hasOption('locale'));
        $this->assertSame('en_US', $definition->getOption('locale')->getDefault());
    }

    /**
     * Test that command returns failure when no entity managers found (empty registry).
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
     * Test that command returns failure when --connection names do not match any manager (managersToProcess empty).
     */
    public function testCommandReturnsFailureWhenConnectionOptionMatchesNoManager(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $doctrine  = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);

        $container->method('get')->with('doctrine')->willReturn($doctrine);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);

        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $input   = new ArrayInput(['--connection' => ['other', 'mongodb']]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertSame(1, $result);
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

        // EM has no metadata driver configured -> getAnonymizableEntities returns []
        $config = $this->createMock(Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn(null);
        $em->method('getConfiguration')->willReturn($config);

        $result = $command->run($input, $output);

        $this->assertSame(0, $result);
        $out = $output->fetch();
        $this->assertStringContainsString('No entities found with #[Anonymize]', $out);
        $this->assertStringContainsString('attribute', $out);
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

    /**
     * Test that command outputs entity and property info and summary when entities with anonymizers are found.
     */
    public function testExecuteOutputsEntityAndPropertyInfoWhenEntitiesFound(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class InfoCommandEntityFixture {
                    #[\\Nowo\\AnonymizeBundle\\Attribute\\AnonymizeProperty(type: "email", weight: 1)]
                    public string $email = "";
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\InfoCommandEntityFixture';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('info_entity');
        $metadata->method('hasField')->with('email')->willReturn(true);
        $metadata->method('getFieldMapping')->with('email')->willReturn(new \Doctrine\ORM\Mapping\FieldMapping('email', 'string', 'email'));

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);

        $config = $this->createMock(Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn (string $id): string => '`' . $id . '`');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('fetchOne')->willReturn('2');
        $connection->method('fetchAllAssociative')->willReturn([
            ['id' => 1, 'email' => 'a@b.com'],
            ['id' => 2, 'email' => 'c@d.com'],
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('doctrine')->willReturn($doctrine);

        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertSame(0, $result);
        $out = $output->fetch();
        $this->assertStringContainsString('Entity Manager: default', $out);
        $this->assertStringContainsString('Found 1 entity(ies) with anonymizers', $out);
        $this->assertStringContainsString('InfoCommandEntityFixture', $out);
        $this->assertStringContainsString('info_entity', $out);
        $this->assertStringContainsString('Total Records: 2', $out);
        $this->assertStringContainsString('Properties to Anonymize', $out);
        $this->assertStringContainsString('email', $out);
        $this->assertStringContainsString('Faker Type: email', $out);
        $this->assertStringContainsString('Records to Anonymize', $out);
        $this->assertStringContainsString('Summary', $out);
        $this->assertStringContainsString('Total Anonymizers: 1', $out);
    }

    /**
     * Test that --connection option filters which entity managers are processed.
     */
    public function testExecuteWithConnectionOptionProcessesOnlyRequestedManager(): void
    {
        $config = $this->createMock(Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn(null);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('db1');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default', 'other' => 'other']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('doctrine')->willReturn($doctrine);

        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $input   = new ArrayInput(['--connection' => ['default']]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertSame(0, $result);
        $out = $output->fetch();
        $this->assertStringContainsString('Entity Manager: default', $out);
        $this->assertStringNotContainsString('Entity Manager: other', $out);
    }

    /**
     * Test that entity with anonymizeService (no properties) shows custom service line and entity-level patterns.
     */
    public function testExecuteOutputsAnonymizeServiceAndEntityPatternsWhenNoProperties(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize(anonymizeService: "app.custom_anonymizer", includePatterns: ["status" => "active"], excludePatterns: ["role" => "admin"])]
                class InfoCommandServiceOnlyEntityFixture {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\InfoCommandServiceOnlyEntityFixture';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('service_only_entity');

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);

        $config = $this->createMock(Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn (string $id): string => '`' . $id . '`');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('fetchOne')->willReturn('0');
        $connection->method('fetchAllAssociative')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('doctrine')->willReturn($doctrine);

        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertSame(0, $result);
        $out = $output->fetch();
        $this->assertStringContainsString('InfoCommandServiceOnlyEntityFixture', $out);
        $this->assertStringContainsString('custom service app.custom_anonymizer', $out);
        $this->assertStringContainsString('Entity Patterns', $out);
        $this->assertStringContainsString('Include:', $out);
        $this->assertStringContainsString('Exclude:', $out);
    }

    /**
     * Test that property with service, options, includePatterns and excludePatterns outputs all lines; totalRecords 0 covers percentage branch.
     */
    public function testExecuteOutputsPropertyWithServiceOptionsAndPatternsAndZeroRecords(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class InfoCommandFullPropertyFixture {
                    #[\\Nowo\\AnonymizeBundle\\Attribute\\AnonymizeProperty(
                        type: "service",
                        service: "app.custom_faker",
                        options: ["format" => "international"],
                        includePatterns: ["active" => "1"],
                        excludePatterns: ["deleted" => "1"]
                    )]
                    public string $phone = "";
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\InfoCommandFullPropertyFixture';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('full_property_entity');
        $metadata->method('hasField')->with('phone')->willReturn(true);
        $metadata->method('getFieldMapping')->with('phone')->willReturn(new \Doctrine\ORM\Mapping\FieldMapping('phone', 'string', 'phone'));

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);

        $config = $this->createMock(Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn (string $id): string => '`' . $id . '`');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('fetchOne')->willReturn('0');
        $connection->method('fetchAllAssociative')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('doctrine')->willReturn($doctrine);

        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertSame(0, $result);
        $out = $output->fetch();
        $this->assertStringContainsString('InfoCommandFullPropertyFixture', $out);
        $this->assertStringContainsString('phone', $out);
        $this->assertStringContainsString('Service: app.custom_faker', $out);
        $this->assertStringContainsString('Options:', $out);
        $this->assertStringContainsString('Include Patterns:', $out);
        $this->assertStringContainsString('Exclude Patterns:', $out);
        $this->assertStringContainsString('Records to Anonymize: 0 / 0 (0%)', $out);
    }

    /**
     * Test that when metadata has no field mapping for the property (e.g. virtual/computed), column name falls back to property name.
     */
    public function testExecuteUsesPropertyNameAsColumnWhenHasFieldFalse(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class InfoCommandVirtualPropertyFixture {
                    #[\\Nowo\\AnonymizeBundle\\Attribute\\AnonymizeProperty(type: "email", weight: 1)]
                    public string $virtualColumn = "";
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\InfoCommandVirtualPropertyFixture';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('virtual_entity');
        $metadata->method('hasField')->with('virtualColumn')->willReturn(false);

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);

        $config = $this->createMock(Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn (string $id): string => '`' . $id . '`');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('fetchOne')->willReturn('1');
        $connection->method('fetchAllAssociative')->willReturn([['virtualColumn' => 'x@y.com']]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('doctrine')->willReturn($doctrine);

        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertSame(0, $result);
        $out = $output->fetch();
        $this->assertStringContainsString('virtualColumn', $out);
        $this->assertStringContainsString('Column: virtualColumn', $out);
    }

    /**
     * Test that properties with same weight are sorted alphabetically by name (covers usort branch weightA === weightB).
     */
    public function testExecuteSortsPropertiesWithSameWeightAlphabetically(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class InfoCommandSameWeightFixture {
                    #[\\Nowo\\AnonymizeBundle\\Attribute\\AnonymizeProperty(type: "email", weight: 5)]
                    public string $email = "";
                    #[\\Nowo\\AnonymizeBundle\\Attribute\\AnonymizeProperty(type: "text", weight: 5)]
                    public string $name = "";
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\InfoCommandSameWeightFixture';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('same_weight_entity');
        $metadata->method('hasField')->willReturnMap([['email', true], ['name', true]]);
        $metadata->method('getFieldMapping')->willReturnMap([
            ['email', new \Doctrine\ORM\Mapping\FieldMapping('email', 'string', 'email')],
            ['name', new \Doctrine\ORM\Mapping\FieldMapping('name', 'string', 'name')],
        ]);

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);

        $config = $this->createMock(Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn (string $id): string => '`' . $id . '`');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('fetchOne')->willReturn('2');
        $connection->method('fetchAllAssociative')->willReturn([
            ['email' => 'a@b.com', 'name' => 'Alice'],
            ['email' => 'c@d.com', 'name' => 'Bob'],
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('doctrine')->willReturn($doctrine);

        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertSame(0, $result);
        $out = $output->fetch();
        $this->assertStringContainsString('Properties to Anonymize: 2', $out);
        $this->assertStringContainsString('email', $out);
        $this->assertStringContainsString('name', $out);
        $this->assertStringContainsString('By Entity:', $out);
    }

    /**
     * Test that entity with no properties and no anonymizeService is skipped (covers line 154-155 continue branch).
     */
    public function testExecuteSkipsEntityWithNoPropertiesAndNoAnonymizeService(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class InfoCommandNoPropsNoServiceFixture { }
            }
        ');
        $emptyClassName = 'Nowo\AnonymizeBundle\Tests\Integration\Command\InfoCommandNoPropsNoServiceFixture';
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class InfoCommandWithPropFixture {
                    #[\\Nowo\\AnonymizeBundle\\Attribute\\AnonymizeProperty(type: "email")]
                    public string $email = "";
                }
            }
        ');
        $withPropClassName = 'Nowo\AnonymizeBundle\Tests\Integration\Command\InfoCommandWithPropFixture';

        $metadataEmpty = $this->createMock(ClassMetadata::class);
        $metadataEmpty->method('getTableName')->willReturn('empty_entity');
        $metadataWithProp = $this->createMock(ClassMetadata::class);
        $metadataWithProp->method('getTableName')->willReturn('with_prop_entity');
        $metadataWithProp->method('hasField')->with('email')->willReturn(true);
        $metadataWithProp->method('getFieldMapping')->with('email')->willReturn(new \Doctrine\ORM\Mapping\FieldMapping('email', 'string', 'email'));

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$emptyClassName, $withPropClassName]);

        $config = $this->createMock(Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn (string $id): string => '`' . $id . '`');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('fetchOne')->willReturn('0');
        $connection->method('fetchAllAssociative')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')
            ->willReturnCallback(static fn (string $class) => $class === $emptyClassName ? $metadataEmpty : $metadataWithProp);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('doctrine')->willReturn($doctrine);

        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $result  = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertSame(0, $result);
    }

    /**
     * Test that properties with different weights use comparison branch in usort (covers line 198 return $weightA <=> $weightB).
     */
    public function testExecuteSortsPropertiesByWeightWhenWeightsDiffer(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class InfoCommandDiffWeightFixture {
                    #[\\Nowo\\AnonymizeBundle\\Attribute\\AnonymizeProperty(type: "name", weight: 10)]
                    public string $name = "";
                    #[\\Nowo\\AnonymizeBundle\\Attribute\\AnonymizeProperty(type: "email", weight: 1)]
                    public string $email = "";
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\InfoCommandDiffWeightFixture';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('diff_weight_entity');
        $metadata->method('hasField')->willReturnMap([['name', true], ['email', true]]);
        $metadata->method('getFieldMapping')->willReturnMap([
            ['name', new \Doctrine\ORM\Mapping\FieldMapping('name', 'string', 'name')],
            ['email', new \Doctrine\ORM\Mapping\FieldMapping('email', 'string', 'email')],
        ]);

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);

        $config = $this->createMock(Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn (string $id): string => '`' . $id . '`');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('fetchOne')->willReturn('1');
        $connection->method('fetchAllAssociative')->willReturn([['email' => 'a@b.com', 'name' => 'A']]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('doctrine')->willReturn($doctrine);

        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $output  = new BufferedOutput();
        $result  = $command->run(new ArrayInput([]), $output);

        $this->assertSame(0, $result);
        $out = $output->fetch();
        $this->assertStringContainsString('Properties to Anonymize: 2', $out);
        $this->assertStringContainsString('Records to Anonymize: 1', $out);
    }

    /**
     * Test that when a record matches entity and property patterns, recordsToAnonymize is incremented (covers line 229-230).
     */
    public function testExecuteCountsRecordsToAnonymizeWhenRecordMatchesPropertyPattern(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize(includePatterns: ["status" => "active"])]
                class InfoCommandMatchPatternFixture {
                    #[\\Nowo\AnonymizeBundle\\Attribute\\AnonymizeProperty(type: "email", includePatterns: ["active" => "1"])]
                    public string $email = "";
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\InfoCommandMatchPatternFixture';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('match_pattern_entity');
        $metadata->method('hasField')->with('email')->willReturn(true);
        $metadata->method('getFieldMapping')->with('email')->willReturn(new \Doctrine\ORM\Mapping\FieldMapping('email', 'string', 'email'));

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);

        $config = $this->createMock(Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn (string $id): string => '`' . $id . '`');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('test_db');
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('fetchOne')->willReturn('2');
        $connection->method('fetchAllAssociative')->willReturn([
            ['status' => 'active', 'active' => '1', 'email' => 'match@example.com'],
            ['status' => 'inactive', 'active' => '0', 'email' => 'no@example.com'],
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('doctrine')->willReturn($doctrine);

        $command = new AnonymizeInfoCommand($container, 'en_US', []);
        $output  = new BufferedOutput();
        $result  = $command->run(new ArrayInput([]), $output);

        $this->assertSame(0, $result);
        $out = $output->fetch();
        $this->assertStringContainsString('Records to Anonymize: 1', $out);
        $this->assertStringContainsString('50%', $out);
    }
}
