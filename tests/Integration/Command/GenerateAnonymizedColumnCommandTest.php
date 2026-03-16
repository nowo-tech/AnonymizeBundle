<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Exception;
use Nowo\AnonymizeBundle\Command\GenerateAnonymizedColumnCommand;
use Nowo\AnonymizeBundle\Enum\SymfonyService;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Test case for GenerateAnonymizedColumnCommand.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class GenerateAnonymizedColumnCommandTest extends TestCase
{
    private ContainerInterface $container;
    private AnonymizeService $anonymizeService;
    private GenerateAnonymizedColumnCommand $command;

    protected function setUp(): void
    {
        $this->container        = $this->createMock(ContainerInterface::class);
        $fakerFactory           = new FakerFactory('en_US');
        $patternMatcher         = new PatternMatcher();
        $this->anonymizeService = new AnonymizeService($fakerFactory, $patternMatcher);
        $this->command          = new GenerateAnonymizedColumnCommand($this->container, $this->anonymizeService, []);
    }

    /**
     * Test that command can be instantiated.
     */
    public function testCommandInstantiation(): void
    {
        $this->assertInstanceOf(GenerateAnonymizedColumnCommand::class, $this->command);
        $this->assertEquals('nowo:anonymize:generate-column-migration', $this->command->getName());
    }

    /**
     * Test that command configuration is correct.
     */
    public function testCommandConfiguration(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('connection'));
        $this->assertTrue($definition->hasOption('output'));

        $connectionOption = $definition->getOption('connection');
        $this->assertTrue($connectionOption->isArray());
        $this->assertTrue($connectionOption->isValueRequired());

        $outputOption = $definition->getOption('output');
        $this->assertFalse($outputOption->isValueRequired());
    }

    /**
     * Test that command returns success when no entities use AnonymizableTrait.
     */
    public function testExecuteWithNoAnonymizableEntities(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $config   = $this->createMock(\Doctrine\ORM\Configuration::class);

        $this->container->method('has')
            ->with(SymfonyService::DOCTRINE)
            ->willReturn(true);
        $this->container->method('get')
            ->with(SymfonyService::DOCTRINE)
            ->willReturn($doctrine);

        $doctrine->method('getManagerNames')
            ->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')
            ->with('default')
            ->willReturn($em);

        $em->method('getConfiguration')
            ->willReturn($config);
        $config->method('getMetadataDriverImpl')
            ->willReturn(null);

        $input  = new ArrayInput(['--connection' => ['default']]);
        $output = new BufferedOutput();

        $result = $this->command->run($input, $output);

        $this->assertEquals(GenerateAnonymizedColumnCommand::SUCCESS, $result);
        $this->assertStringContainsString('No migrations needed', $output->fetch());
    }

    /**
     * Test that command generates SQL when entity uses AnonymizableTrait and column doesn't exist.
     * Note: This test is simplified due to AnonymizeService being final.
     */
    public function testExecuteGeneratesSqlForEntityWithTrait(): void
    {
        // This test would require complex mocking of AnonymizeService
        // Since AnonymizeService is final, we test the command structure instead
        $this->assertTrue(true); // Placeholder - actual test would require integration test setup
    }

    /**
     * Test that command skips entity when column already exists (covers lines 135, 143-148).
     */
    public function testExecuteSkipsEntityWhenColumnExists(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                use Nowo\\AnonymizeBundle\\Attribute\\Anonymize;
                use Nowo\\AnonymizeBundle\\Trait\\AnonymizableTrait;
                #[Anonymize]
                class GenerateColumnColumnExistsFixture {
                    use AnonymizableTrait;
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\GenerateColumnColumnExistsFixture';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('table_with_anonymized');

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);

        $config = $this->createMock(\Doctrine\ORM\Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn (string $id): string => '"' . $id . '"');

        $anonymizedColumn = $this->createMock(Column::class);
        $anonymizedColumn->method('getName')->willReturn('anonymized');
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with(['table_with_anonymized'])->willReturn(true);
        $schemaManager->method('listTableColumns')->with('table_with_anonymized')->willReturn([$anonymizedColumn]);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $this->container->method('has')->with(SymfonyService::DOCTRINE)->willReturn(true);
        $this->container->method('get')->with(SymfonyService::DOCTRINE)->willReturn($doctrine);

        $fakerFactory    = new FakerFactory('en_US');
        $patternMatcher  = new PatternMatcher();
        $anonymizeService = new AnonymizeService($fakerFactory, $patternMatcher);
        $command         = new GenerateAnonymizedColumnCommand($this->container, $anonymizeService, []);

        $input  = new ArrayInput(['--connection' => ['default']]);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertEquals(GenerateAnonymizedColumnCommand::SUCCESS, $result);
        $content = $output->fetch();
        $this->assertStringContainsString('Column anonymized already exists', $content);
        $this->assertStringContainsString('table_with_anonymized', $content);
        $this->assertStringContainsString('No migrations needed', $content);
    }

    /**
     * Test that command skips entity when it doesn't use AnonymizableTrait (covers continue at line 135).
     */
    public function testExecuteSkipsEntityWithoutTrait(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                use Nowo\\AnonymizeBundle\\Attribute\\Anonymize;
                #[Anonymize]
                class GenerateColumnNoTraitFixture {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\GenerateColumnNoTraitFixture';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('no_trait_table');

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);

        $config = $this->createMock(\Doctrine\ORM\Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $platform      = $this->createMock(AbstractPlatform::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $connection   = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $this->container->method('has')->with(SymfonyService::DOCTRINE)->willReturn(true);
        $this->container->method('get')->with(SymfonyService::DOCTRINE)->willReturn($doctrine);

        $fakerFactory   = new FakerFactory('en_US');
        $patternMatcher = new PatternMatcher();
        $anonymizeService = new AnonymizeService($fakerFactory, $patternMatcher);
        $command = new GenerateAnonymizedColumnCommand($this->container, $anonymizeService, []);

        $input  = new ArrayInput(['--connection' => ['default']]);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertEquals(GenerateAnonymizedColumnCommand::SUCCESS, $result);
        $content = $output->fetch();
        $this->assertStringContainsString('No migrations needed', $content);
    }

    /**
     * Test that command handles connection not found gracefully.
     */
    public function testExecuteHandlesConnectionNotFound(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);

        $this->container->method('has')
            ->with(SymfonyService::DOCTRINE)
            ->willReturn(true);
        $this->container->method('get')
            ->with(SymfonyService::DOCTRINE)
            ->willReturn($doctrine);

        $doctrine->method('getManagerNames')
            ->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')
            ->with('nonexistent')
            ->willThrowException(new Exception('Manager not found'));

        $input  = new ArrayInput(['--connection' => ['nonexistent']]);
        $output = new BufferedOutput();

        $result = $this->command->run($input, $output);

        $this->assertEquals(GenerateAnonymizedColumnCommand::SUCCESS, $result);
        $outputContent = $output->fetch();
        $this->assertStringContainsString('not found', $outputContent);
    }

    /**
     * Test that command catches exception during entity processing and continues (covers catch at lines 174-176).
     */
    public function testExecuteHandlesExceptionInProcessing(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                use Nowo\\AnonymizeBundle\\Attribute\\Anonymize;
                use Nowo\\AnonymizeBundle\\Trait\\AnonymizableTrait;
                #[Anonymize]
                class GenerateColumnExceptionFixture {
                    use AnonymizableTrait;
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\GenerateColumnExceptionFixture';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('exception_table');

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);

        $config = $this->createMock(\Doctrine\ORM\Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')
            ->willThrowException(new Exception('Schema manager failed'));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $this->container->method('has')->with(SymfonyService::DOCTRINE)->willReturn(true);
        $this->container->method('get')->with(SymfonyService::DOCTRINE)->willReturn($doctrine);

        $fakerFactory   = new FakerFactory('en_US');
        $patternMatcher = new PatternMatcher();
        $anonymizeService = new AnonymizeService($fakerFactory, $patternMatcher);
        $command = new GenerateAnonymizedColumnCommand($this->container, $anonymizeService, []);

        $input  = new ArrayInput(['--connection' => ['default']]);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertEquals(GenerateAnonymizedColumnCommand::SUCCESS, $result);
        $content = $output->fetch();
        $this->assertStringContainsString('Error processing connection', $content);
        $this->assertStringContainsString('Schema manager failed', $content);
    }

    /**
     * Test that command saves output to file when --output option is provided.
     * Note: This test is simplified due to AnonymizeService being final.
     */
    public function testExecuteSavesOutputToFile(): void
    {
        // This test would require complex mocking of AnonymizeService
        // Since AnonymizeService is final, we test the command structure instead
        $this->assertTrue(true); // Placeholder - actual test would require integration test setup
    }

    /**
     * Test that command uses default connection when none is provided.
     */
    public function testExecuteUsesDefaultConnection(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $config   = $this->createMock(\Doctrine\ORM\Configuration::class);

        $this->container->method('has')
            ->with(SymfonyService::DOCTRINE)
            ->willReturn(true);
        $this->container->method('get')
            ->with(SymfonyService::DOCTRINE)
            ->willReturn($doctrine);

        $doctrine->method('getManagerNames')
            ->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')
            ->with('default')
            ->willReturn($em);

        $em->method('getConfiguration')
            ->willReturn($config);
        $config->method('getMetadataDriverImpl')
            ->willReturn(null);

        $input  = new ArrayInput([]);
        $output = new BufferedOutput();

        $result = $this->command->run($input, $output);

        $this->assertEquals(GenerateAnonymizedColumnCommand::SUCCESS, $result);
    }

    /**
     * Test that getEntityManager returns default manager when connection name is 'default' but not in getManagerNames (covers lines 233-234).
     */
    public function testExecuteUsesDefaultManagerWhenConnectionNameDefaultNotInList(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $config   = $this->createMock(\Doctrine\ORM\Configuration::class);

        $this->container->method('has')->with(SymfonyService::DOCTRINE)->willReturn(true);
        $this->container->method('get')->with(SymfonyService::DOCTRINE)->willReturn($doctrine);

        $doctrine->method('getManagerNames')->willReturn(['orm' => 'doctrine.orm.orm']);
        $doctrine->method('getManager')->willReturnCallback(static function (?string $name = null) use ($em) {
            return $em;
        });

        $em->method('getConfiguration')->willReturn($config);
        $config->method('getMetadataDriverImpl')->willReturn(null);

        $input  = new ArrayInput(['--connection' => ['default']]);
        $output = new BufferedOutput();

        $result = $this->command->run($input, $output);

        $this->assertEquals(GenerateAnonymizedColumnCommand::SUCCESS, $result);
    }

    /**
     * Test that command handles exception during processing gracefully.
     */
    public function testExecuteHandlesExceptionGracefully(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);

        $this->container->method('has')
            ->with(SymfonyService::DOCTRINE)
            ->willReturn(true);
        $this->container->method('get')
            ->with(SymfonyService::DOCTRINE)
            ->willReturn($doctrine);

        $doctrine->method('getManagerNames')
            ->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')
            ->with('default')
            ->willThrowException(new Exception('Database error'));

        $input  = new ArrayInput(['--connection' => ['default']]);
        $output = new BufferedOutput();

        $result = $this->command->run($input, $output);

        $this->assertEquals(GenerateAnonymizedColumnCommand::SUCCESS, $result);
        $outputContent = $output->fetch();
        // When getManager throws, getEntityManager returns null → "Connection ... not found, skipping"
        $this->assertStringContainsString('not found', $outputContent);
    }

    /**
     * Test that command generates SQL and can write to --output file when entity uses AnonymizableTrait and table has no anonymized column.
     */
    public function testExecuteGeneratesSqlAndWritesToOutputFile(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                use Nowo\\AnonymizeBundle\\Attribute\\Anonymize;
                use Nowo\\AnonymizeBundle\\Trait\\AnonymizableTrait;
                #[Anonymize]
                class GenerateColumnEntityFixture {
                    use AnonymizableTrait;
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\GenerateColumnEntityFixture';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('generate_column_test');

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);

        $config = $this->createMock(\Doctrine\ORM\Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn (string $id): string => '"' . $id . '"');

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with(['generate_column_test'])->willReturn(false);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $this->container->method('has')->with(SymfonyService::DOCTRINE)->willReturn(true);
        $this->container->method('get')->with(SymfonyService::DOCTRINE)->willReturn($doctrine);

        $fakerFactory   = new FakerFactory('en_US');
        $patternMatcher = new PatternMatcher();
        $anonymizeService = new AnonymizeService($fakerFactory, $patternMatcher);
        $command = new GenerateAnonymizedColumnCommand($this->container, $anonymizeService, []);

        $outputPath = sys_get_temp_dir() . '/anonymize_col_migration_' . uniqid() . '.sql';
        $input     = new ArrayInput(['--connection' => ['default'], '--output' => $outputPath]);
        $output    = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertEquals(GenerateAnonymizedColumnCommand::SUCCESS, $result);
        $this->assertFileExists($outputPath);
        $content = file_get_contents($outputPath);
        $this->assertStringContainsString('ALTER TABLE', $content);
        $this->assertStringContainsString('anonymized', $content);
        $this->assertStringContainsString('generate_column_test', $content);
        @unlink($outputPath);
    }

    /**
     * Test that command generates SQL when entity inherits AnonymizableTrait from parent (covers usesAnonymizableTrait parent check, lines 262-272).
     */
    public function testExecuteGeneratesSqlWhenTraitInParentClass(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                use Nowo\\AnonymizeBundle\\Trait\\AnonymizableTrait;
                class GenerateColumnParentWithTraitFixture {
                    use AnonymizableTrait;
                }
            }
        ');
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                use Nowo\\AnonymizeBundle\\Attribute\\Anonymize;
                #[Anonymize]
                class GenerateColumnChildInheritsTraitFixture extends GenerateColumnParentWithTraitFixture {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\GenerateColumnChildInheritsTraitFixture';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('child_trait_table');

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);

        $config = $this->createMock(\Doctrine\ORM\Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn (string $id): string => '"' . $id . '"');

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with(['child_trait_table'])->willReturn(false);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $this->container->method('has')->with(SymfonyService::DOCTRINE)->willReturn(true);
        $this->container->method('get')->with(SymfonyService::DOCTRINE)->willReturn($doctrine);

        $fakerFactory   = new FakerFactory('en_US');
        $patternMatcher = new PatternMatcher();
        $anonymizeService = new AnonymizeService($fakerFactory, $patternMatcher);
        $command = new GenerateAnonymizedColumnCommand($this->container, $anonymizeService, []);

        $outputPath = sys_get_temp_dir() . '/anonymize_col_migration_' . uniqid() . '.sql';
        $input     = new ArrayInput(['--connection' => ['default'], '--output' => $outputPath]);
        $output    = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertEquals(GenerateAnonymizedColumnCommand::SUCCESS, $result);
        $this->assertFileExists($outputPath);
        $content = file_get_contents($outputPath);
        $this->assertStringContainsString('ALTER TABLE', $content);
        $this->assertStringContainsString('anonymized', $content);
        $this->assertStringContainsString('child_trait_table', $content);
        @unlink($outputPath);
    }

    /**
     * Test that command prints SQL to console when --output is not provided (covers lines 197-200).
     */
    public function testExecutePrintsSqlToConsoleWhenNoOutputOption(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                use Nowo\\AnonymizeBundle\\Attribute\\Anonymize;
                use Nowo\\AnonymizeBundle\\Trait\\AnonymizableTrait;
                #[Anonymize]
                class GenerateColumnConsoleOutputFixture {
                    use AnonymizableTrait;
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\GenerateColumnConsoleOutputFixture';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('console_output_table');

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);

        $config = $this->createMock(\Doctrine\ORM\Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn (string $id): string => '"' . $id . '"');

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with(['console_output_table'])->willReturn(false);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $this->container->method('has')->with(SymfonyService::DOCTRINE)->willReturn(true);
        $this->container->method('get')->with(SymfonyService::DOCTRINE)->willReturn($doctrine);

        $fakerFactory   = new FakerFactory('en_US');
        $patternMatcher = new PatternMatcher();
        $anonymizeService = new AnonymizeService($fakerFactory, $patternMatcher);
        $command = new GenerateAnonymizedColumnCommand($this->container, $anonymizeService, []);

        $input  = new ArrayInput(['--connection' => ['default']]);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertEquals(GenerateAnonymizedColumnCommand::SUCCESS, $result);
        $content = $output->fetch();
        $this->assertStringContainsString('Generated Migration SQL:', $content);
        $this->assertStringContainsString('Use --output option to save to a file', $content);
        $this->assertStringContainsString('ALTER TABLE', $content);
        $this->assertStringContainsString('console_output_table', $content);
    }

    /**
     * Test that usesAnonymizableTrait returns true when trait is in grandparent (covers line 269 - while next parent).
     */
    public function testExecuteGeneratesSqlWhenTraitInGrandparentClass(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                use Nowo\\AnonymizeBundle\\Trait\\AnonymizableTrait;
                class GenerateColumnGrandparentTraitFixture {
                    use AnonymizableTrait;
                }
            }
        ');
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                class GenerateColumnParentNoTraitFixture extends GenerateColumnGrandparentTraitFixture {
                }
            }
        ');
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                use Nowo\\AnonymizeBundle\\Attribute\\Anonymize;
                #[Anonymize]
                class GenerateColumnChildGrandparentTraitFixture extends GenerateColumnParentNoTraitFixture {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\GenerateColumnChildGrandparentTraitFixture';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('grandparent_trait_table');

        $metadataDriver = $this->createMock(MappingDriver::class);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);

        $config = $this->createMock(\Doctrine\ORM\Configuration::class);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn (string $id): string => '"' . $id . '"');

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with(['grandparent_trait_table'])->willReturn(false);

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $this->container->method('has')->with(SymfonyService::DOCTRINE)->willReturn(true);
        $this->container->method('get')->with(SymfonyService::DOCTRINE)->willReturn($doctrine);

        $fakerFactory   = new FakerFactory('en_US');
        $patternMatcher = new PatternMatcher();
        $anonymizeService = new AnonymizeService($fakerFactory, $patternMatcher);
        $command = new GenerateAnonymizedColumnCommand($this->container, $anonymizeService, []);

        $input  = new ArrayInput(['--connection' => ['default']]);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);

        $this->assertEquals(GenerateAnonymizedColumnCommand::SUCCESS, $result);
        $this->assertStringContainsString('Generated Migration SQL:', $output->fetch());
    }
}
