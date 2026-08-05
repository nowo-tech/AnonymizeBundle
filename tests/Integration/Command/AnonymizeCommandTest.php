<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Exception;
use Nowo\AnonymizeBundle\Command\AnonymizeCommand;
use Nowo\AnonymizeBundle\Event\BeforeAnonymizeEvent;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\AnonymizationHistoryService;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\EntityAnonymizerServiceInterface;
use Nowo\AnonymizeBundle\Service\EnvironmentProtectionService;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
use Nowo\AnonymizeBundle\Service\PreFlightCheckService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Test case for AnonymizeCommand.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizeCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/anonymize_cmd_test_' . uniqid();
        mkdir($this->tempDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

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

    /**
     * Test command name and that it extends the base.
     */
    public function testCommandNameAndDescription(): void
    {
        $command = $this->createDefaultAnonymizeCommand($this->createMock(ManagerRegistry::class));

        $this->assertSame('nowo:anonymize:run', $command->getName());
        $this->assertStringContainsString('Anonymize database records', $command->getDescription());
    }

    /**
     * Test that configure defines expected options.
     */
    public function testConfigureDefinesOptions(): void
    {
        $command    = $this->createDefaultAnonymizeCommand($this->createMock(ManagerRegistry::class));
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('connection'));
        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertTrue($definition->hasOption('batch-size'));
        $this->assertTrue($definition->hasOption('locale'));
        $this->assertTrue($definition->hasOption('stats-json'));
        $this->assertTrue($definition->hasOption('stats-csv'));
        $this->assertTrue($definition->hasOption('stats-only'));
        $this->assertTrue($definition->hasOption('no-progress'));
        $this->assertTrue($definition->hasOption('debug'));
        $this->assertTrue($definition->hasOption('interactive'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('entity'));
    }

    /**
     * Test execute with --entity option when no anonymizable entities exist: shows entity-filter message (not generic "no entities").
     */
    public function testExecuteWithEntityOptionAndNoEntities(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--entity' => ['App\Entity\SmsNotification']]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(0, $exitCode);
        $out = $output->fetch();
        $this->assertStringContainsString('No entities matching --entity filter in', $out);
        $this->assertStringContainsString('manager "default"', $out);
    }

    /**
     * Test that when --entity filter is used and no entity in the manager matches, the specific filter message is shown.
     */
    public function testExecuteWithEntityFilterShowsMessageWhenNoEntityMatches(): void
    {
        // Create a real class with #[Anonymize] so getAnonymizableEntities returns it
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class EntityFilterTestEntity {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\EntityFilterTestEntity';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('entity_filter_test');
        // Do not mock isMappedSuperclass/isEmbeddedClass (final or not mockable in some ORM versions)

        $metadataDriver = $this->createMock(MappingDriver::class);
        $config         = $this->createMock(Configuration::class);
        $em             = $this->createMock(EntityManagerInterface::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(Result::class));

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->willReturn(true);

        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--entity' => ['App\Entity\NonExistentEntity']]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(0, $exitCode);
        $out = $output->fetch();
        $this->assertStringContainsString('No entities matching --entity filter in', $out);
        $this->assertStringContainsString('manager "default"', $out);
    }

    /**
     * Test that when event_dispatcher is present and a listener clears entity classes via BeforeAnonymizeEvent, no entities are processed (lines 449-455).
     */
    public function testExecuteWhenBeforeAnonymizeEventListenerClearsEntitiesProcessesNone(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class EventDispatchTestEntity {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\EventDispatchTestEntity';
        $tableName = 'event_dispatch_test';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn($tableName);

        $metadataDriver = $this->createMock(MappingDriver::class);
        $config         = $this->createMock(Configuration::class);
        $em             = $this->createMock(EntityManagerInterface::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(Result::class));

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with([$tableName])->willReturn(true);

        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(BeforeAnonymizeEvent::class, static function (BeforeAnonymizeEvent $event): void {
            $event->setEntityClasses([]);
        });

        $command = $this->createDefaultAnonymizeCommand($doctrine, null, null, $eventDispatcher);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Found 0 entity(ies) to process', $out);
    }

    /**
     * Test that when an entity has #[Anonymize] but no #[AnonymizeProperty], command shows "No properties found" and skips (lines 589-597).
     */
    public function testExecuteWhenEntityHasNoAnonymizePropertyShowsNoteAndSkips(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class NoPropertiesTestEntity {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\NoPropertiesTestEntity';
        $tableName = 'no_properties_test';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn($tableName);

        $metadataDriver = $this->createMock(MappingDriver::class);
        $config         = $this->createMock(Configuration::class);
        $em             = $this->createMock(EntityManagerInterface::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(Result::class));

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with([$tableName])->willReturn(true);

        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Found 1 entity(ies) to process', $out);
        $this->assertStringContainsString('No properties found with #[AnonymizeProperty] attribute', $out);
    }

    /**
     * Test that with --debug and entity having no AnonymizeProperty, command outputs debug "Skipping entity (no anonymizable properties)" (lines 594-596).
     */
    public function testExecuteWithDebugWhenEntityHasNoPropertiesShowsDebugSkipMessage(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class NoPropsDebugTestEntity {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\NoPropsDebugTestEntity';
        $tableName = 'no_props_debug_test';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn($tableName);

        $metadataDriver = $this->createMock(MappingDriver::class);
        $config         = $this->createMock(Configuration::class);
        $em             = $this->createMock(EntityManagerInterface::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(Result::class));

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with([$tableName])->willReturn(true);

        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--debug' => true]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Skipping entity (no anonymizable properties)', $out);
        $this->assertStringContainsString('[DEBUG]', $out);
    }

    /**
     * Test that when entity has anonymizeService and no #[AnonymizeProperty], command shows "Using custom anonymize service" and runs anonymization (lines 560-561, 600-601, 608-609).
     */
    public function testExecuteWhenEntityUsesCustomAnonymizeServiceAndNoPropertiesShowsMessageAndProcesses(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize(anonymizeService: "custom_anonymizer")]
                class CustomServiceNoPropsTestEntity {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\CustomServiceNoPropsTestEntity';
        $tableName = 'custom_service_no_props_test';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn($tableName);
        $metadata->method('getIdentifierColumnNames')->willReturn(['id']);
        $metadata->method('hasField')->willReturn(false);
        $metadata->method('hasAssociation')->willReturn(false);
        $metadata->method('getFieldNames')->willReturn([]);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn ($id): string => '`' . $id . '`');

        $record     = ['id' => 1];
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(Result::class));
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('quote')->willReturnCallback(static fn ($v): string => "'" . str_replace("'", "''", (string) $v) . "'");
        $connection->method('fetchOne')->willReturn('1');
        $fetchCallCount = 0;
        $connection->method('fetchAllAssociative')->willReturnCallback(static function () use ($record, &$fetchCallCount): array {
            ++$fetchCallCount;

            return $fetchCallCount === 1 ? [$record] : [];
        });
        $connection->method('beginTransaction')->willReturnCallback(static function (): void {});
        $connection->method('commit')->willReturnCallback(static function (): void {});

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with([$tableName])->willReturn(true);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $anonymizer = $this->createMock(EntityAnonymizerServiceInterface::class);
        $anonymizer->method('supportsBatch')->willReturn(false);
        $anonymizer->method('anonymize')->willReturn([]);

        $metadataDriver = $this->createMock(MappingDriver::class);
        $config         = $this->createMock(Configuration::class);
        $em             = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $anonymizerRegistry = $this->createMock(ContainerInterface::class);
        $anonymizerRegistry->method('has')->willReturnCallback(static fn (string $id): bool => $id === 'custom_anonymizer');
        $anonymizerRegistry->method('get')->willReturnCallback(static fn (string $id): mixed => $id === 'custom_anonymizer' ? $anonymizer : null);

        $command = $this->createDefaultAnonymizeCommand($doctrine, null, null, null, $anonymizerRegistry);
        $input   = new ArrayInput(['--debug' => true]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Using custom anonymize service (no #[AnonymizeProperty] needed)', $out);
        $this->assertStringContainsString('Anonymization: custom service (no property attributes)', $out);
        $this->assertStringContainsString('Processed: 1 records', $out);
    }

    /**
     * Test that with --no-progress the command processes entities without creating a progress bar (covers noProgress branch lines 643-645, 675-677).
     */
    public function testExecuteWithNoProgressProcessesWithoutProgressBar(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize(anonymizeService: "custom_anonymizer")]
                class NoProgressTestEntity {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\NoProgressTestEntity';
        $tableName = 'no_progress_test';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn($tableName);
        $metadata->method('getIdentifierColumnNames')->willReturn(['id']);
        $metadata->method('hasField')->willReturn(false);
        $metadata->method('hasAssociation')->willReturn(false);
        $metadata->method('getFieldNames')->willReturn([]);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn ($id): string => '`' . $id . '`');

        $record     = ['id' => 1];
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(Result::class));
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('quote')->willReturnCallback(static fn ($v): string => "'" . str_replace("'", "''", (string) $v) . "'");
        $connection->method('fetchOne')->willReturn('1');
        $fetchCallCount = 0;
        $connection->method('fetchAllAssociative')->willReturnCallback(static function () use ($record, &$fetchCallCount): array {
            ++$fetchCallCount;

            return $fetchCallCount === 1 ? [$record] : [];
        });
        $connection->method('beginTransaction')->willReturnCallback(static function (): void {});
        $connection->method('commit')->willReturnCallback(static function (): void {});

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with([$tableName])->willReturn(true);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $anonymizer = $this->createMock(EntityAnonymizerServiceInterface::class);
        $anonymizer->method('supportsBatch')->willReturn(false);
        $anonymizer->method('anonymize')->willReturn([]);

        $metadataDriver = $this->createMock(MappingDriver::class);
        $config         = $this->createMock(Configuration::class);
        $em             = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $anonymizerRegistry = $this->createMock(ContainerInterface::class);
        $anonymizerRegistry->method('has')->willReturnCallback(static fn (string $id): bool => $id === 'custom_anonymizer');
        $anonymizerRegistry->method('get')->willReturnCallback(static fn (string $id): mixed => $id === 'custom_anonymizer' ? $anonymizer : null);

        $command = $this->createDefaultAnonymizeCommand($doctrine, null, null, null, $anonymizerRegistry);
        $input   = new ArrayInput(['--no-progress' => true]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Processed: 1 records', $out);
    }

    /**
     * Test that when an entity has #[AnonymizeProperty] and at least one record is anonymized, displayStatistics shows "Property Statistics" section (lines 810-825).
     */
    public function testExecuteWithPropertyAnonymizationShowsPropertyStatisticsSection(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class PropertyStatsDisplayTestEntity {
                    #[\\Nowo\\AnonymizeBundle\\Attribute\\AnonymizeProperty(type: "email")]
                    public string $email = "";
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\PropertyStatsDisplayTestEntity';
        $tableName = 'property_stats_display_test';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn($tableName);
        $metadata->method('getIdentifierColumnNames')->willReturn(['id']);
        $metadata->method('hasField')->willReturn(true);
        $metadata->method('hasAssociation')->willReturn(false);
        $metadata->method('getFieldNames')->willReturn(['id', 'email']);
        $metadata->method('getFieldMapping')->willReturnMap([
            ['id', new FieldMapping('id', 'integer', 'id')],
            ['email', new FieldMapping('email', 'string', 'email')],
        ]);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn ($id): string => '`' . $id . '`');

        $record     = ['id' => 1, 'email' => 'user@example.com'];
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(Result::class));
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('quote')->willReturnCallback(static fn ($v): string => "'" . str_replace("'", "''", (string) $v) . "'");
        $connection->method('fetchOne')->willReturn('1');
        $fetchCallCount = 0;
        $connection->method('fetchAllAssociative')->willReturnCallback(static function () use ($record, &$fetchCallCount): array {
            ++$fetchCallCount;

            return $fetchCallCount === 1 ? [$record] : [];
        });
        $connection->method('beginTransaction')->willReturnCallback(static function (): void {});
        $connection->method('commit')->willReturnCallback(static function (): void {});
        $connection->method('executeStatement')->willReturn(1);

        $idColumn = $this->createMock(Column::class);
        $idColumn->method('getName')->willReturn('id');
        $emailColumn = $this->createMock(Column::class);
        $emailColumn->method('getName')->willReturn('email');

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with([$tableName])->willReturn(true);
        $schemaManager->method('listTableColumns')->with($tableName)->willReturn(['id' => $idColumn, 'email' => $emailColumn]);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $metadataDriver = $this->createMock(MappingDriver::class);
        $config         = $this->createMock(Configuration::class);
        $em             = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--no-progress' => true]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Property Statistics', $out);
        $this->assertStringContainsString('Anonymized Count', $out);
        $this->assertStringContainsString('email', $out);
        $this->assertStringContainsString('Processed: 1 records', $out);
    }

    /**
     * Test execute returns failure when pre-flight checks fail (e.g. table does not exist).
     */
    public function testExecuteFailsWhenPreFlightChecksFail(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class PreFlightFailTestEntity {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\PreFlightFailTestEntity';
        $tableName = 'pre_flight_fail_test';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn($tableName);

        $metadataDriver = $this->createMock(MappingDriver::class);
        $config         = $this->createMock(Configuration::class);
        $em             = $this->createMock(EntityManagerInterface::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(Result::class));

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with([$tableName])->willReturn(false);

        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Pre-flight checks failed', $out);
        $this->assertStringContainsString('does not exist', $out);
    }

    /**
     * Test execute returns failure when environment protection checks fail (e.g. prod).
     */
    public function testExecuteFailsWhenEnvironmentChecksFail(): void
    {
        $environmentProtection = new EnvironmentProtectionService(new ParameterBag([
            'kernel.environment' => 'prod',
            'kernel.project_dir' => $this->tempDir,
        ]), []);

        $command = $this->createDefaultAnonymizeCommand($this->createMock(ManagerRegistry::class), null, null, null, null, $environmentProtection);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Environment protection checks failed', $output->fetch());
    }

    /**
     * Test execute returns failure when no entity managers are available.
     */
    public function testExecuteFailsWhenNoEntityManagers(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn([]);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('No entity managers found to process', $output->fetch());
    }

    /**
     * Test execute succeeds when managers exist but have no anonymizable entities.
     */
    public function testExecuteSucceedsWhenManagersExistButNoAnonymizableEntities(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(0, $exitCode);
        $out = $output->fetch();
        $this->assertStringContainsString('No entities found with #[Anonymize]', $out);
        $this->assertStringContainsString('attribute', $out);
    }

    /**
     * Test that --debug option shows debug message.
     */
    public function testExecuteWithDebugShowsDebugMessage(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--debug' => true]);
        $output  = new BufferedOutput();

        $command->run($input, $output);

        $this->assertStringContainsString('DEBUG MODE', $output->fetch());
    }

    /**
     * Test that MongoDB in --connection shows warning.
     */
    public function testExecuteWithMongoConnectionShowsWarning(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--connection' => ['mongodb']]);
        $output  = new BufferedOutput();

        $command->run($input, $output);

        $out = $output->fetch();
        $this->assertStringContainsString('MongoDB ODM support is not yet', $out);
        $this->assertStringContainsString('available. The command', $out);
    }

    /**
     * Test dry-run option shows warning and command still runs.
     */
    public function testExecuteWithDryRunShowsWarning(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--dry-run' => true]);
        $output  = new BufferedOutput();

        $command->run($input, $output);

        $this->assertStringContainsString('DRY RUN MODE', $output->fetch());
    }

    /**
     * Test stats-only option runs without detailed output.
     */
    public function testExecuteWithStatsOnly(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--stats-only' => true]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Anonymization Statistics', $output->fetch());
    }

    /**
     * Test that --connection option filters which entity managers are processed.
     */
    public function testExecuteWithConnectionOptionProcessesOnlyRequestedManager(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default', 'other' => 'other']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--connection' => ['default']]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Processing entity manager: default', $out);
        $this->assertStringNotContainsString('Processing entity manager: other', $out);
    }

    /**
     * Test that command returns failure when getManager() throws.
     */
    public function testExecuteReturnsFailureWhenGetManagerThrows(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willThrowException(new Exception('Connection failed'));

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Error processing entity manager', $out);
    }

    /**
     * Absolute --stats-json under stats_output_dir is accepted.
     */
    public function testExecuteWithStatsJsonAbsolutePathInsideStatsDirExports(): void
    {
        $statsDir = $this->tempDir . '/stats';
        mkdir($statsDir, 0o755, true);
        $absoluteJsonPath = $statsDir . '/absolute_run_stats.json';

        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--stats-json' => $absoluteJsonPath]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Statistics exported to JSON', $out);
        $this->assertFileExists($absoluteJsonPath);
        $json = file_get_contents($absoluteJsonPath);
        $this->assertIsString($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('global', $data);
    }

    /**
     * Absolute --stats-json outside stats_output_dir is rejected (path escape guard).
     */
    public function testExecuteWithStatsJsonAbsolutePathOutsideStatsDirIsRejected(): void
    {
        $absoluteJsonPath = $this->tempDir . '/absolute_run_stats.json';

        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--stats-json' => $absoluteJsonPath]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(2, $exitCode);
        $this->assertFileDoesNotExist($absoluteJsonPath);
    }

    /**
     * Test that --stats-json with relative path creates output dir and exports JSON file.
     */
    public function testExecuteWithStatsJsonRelativePathExportsFile(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--stats-json' => 'run_stats.json']);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Statistics exported to JSON', $out);
        // stats_output_dir from createDefaultParameterBag is $tempDir/stats
        $statsDir = $this->tempDir . '/stats';
        $this->assertDirectoryExists($statsDir);
        $jsonPath = $statsDir . '/run_stats.json';
        $this->assertFileExists($jsonPath);
        $json = file_get_contents($jsonPath);
        $this->assertNotFalse($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('global', $data);
    }

    /**
     * Test that --stats-csv with relative path creates output dir and exports CSV file.
     */
    public function testExecuteWithStatsCsvRelativePathExportsFile(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--stats-csv' => 'run_stats.csv']);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Statistics exported to CSV', $out);
        $csvPath = $this->tempDir . '/stats/run_stats.csv';
        $this->assertFileExists($csvPath);
        $csvContent = file_get_contents($csvPath);
        $this->assertIsString($csvContent);
        $this->assertStringContainsString('Total Processed', $csvContent);
    }

    /**
     * Test that --dry-run shows DRY RUN MODE warning.
     */
    public function testExecuteShowsDryRunWarningWhenDryRunOption(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--dry-run' => true]);
        $output  = new BufferedOutput();

        $command->run($input, $output);
        $this->assertStringContainsString('DRY RUN MODE', $output->fetch());
    }

    /**
     * Test that --debug shows DEBUG MODE note.
     */
    public function testExecuteShowsDebugNoteWhenDebugOption(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--debug' => true]);
        $output  = new BufferedOutput();

        $command->run($input, $output);
        $this->assertStringContainsString('DEBUG MODE', $output->fetch());
    }

    /**
     * Test that when --connection mongodb is requested together with another connection, MongoDB warning is shown.
     */
    public function testExecuteShowsMongoDBWarningWhenConnectionMongoRequested(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--connection' => ['default', 'mongodb']]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('MongoDB ODM support is not yet', $out);
        $this->assertStringContainsString('available. The command', $out);
        $this->assertStringContainsString('Processing entity manager: default', $out);
    }

    /**
     * Test that --batch-size option is accepted and command runs (option is passed to processConnection).
     */
    public function testExecuteWithBatchSizeOptionRunsSuccessfully(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--batch-size' => '50']);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Processing entity manager: default', $output->fetch());
    }

    /**
     * Test that verbose output (via output verbosity) shows VERBOSE MODE note.
     */
    public function testExecuteWithVerboseShowsNote(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();
        $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

        $command->run($input, $output);
        $this->assertStringContainsString('VERBOSE MODE', $output->fetch());
    }

    /**
     * Test that when --interactive is used and user answers "no" to the first confirmation, command shows cancellation message and returns success.
     */
    public function testExecuteInteractiveUserCancelsShowsWarningAndReturnsSuccess(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--interactive' => true]);
        $stream  = fopen('data://text/plain,no', 'r');
        $this->assertIsResource($stream);
        $input->setStream($stream);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Anonymization cancelled by user', $out);
    }

    /**
     * Test that when --interactive is used and user answers "yes" to the first confirmation, command shows summary and continues (lines 270-287).
     */
    public function testExecuteInteractiveUserConfirmsProceedShowsSummaryAndContinues(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--interactive' => true]);
        $stream  = fopen('data://text/plain,y', 'r');
        $this->assertIsResource($stream);
        $input->setStream($stream);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Interactive Mode - Anonymization Summary', $out);
        $this->assertStringContainsString('Entity managers to process', $out);
        $this->assertStringContainsString('Batch size', $out);
        $this->assertStringContainsString('Do you want to proceed with anonymization?', $out);
        $this->assertStringContainsString('Processing entity manager: default', $out);
    }

    /**
     * Test that when saving history fails and --debug is set, the command outputs a debug message (catch block lines 373-376).
     */
    public function testExecuteWhenHistorySaveFailsAndDebugShowsDebugMessage(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $historyDirPath = $this->tempDir . '/history';
        file_put_contents($historyDirPath, '');
        $historyService = new AnonymizationHistoryService($historyDirPath);

        $command = $this->createDefaultAnonymizeCommand($doctrine, null, $historyService);
        $input   = new ArrayInput(['--debug' => true]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Failed to save history', $out);
        $this->assertStringContainsString('[DEBUG]', $out);
    }

    /**
     * Test that when no entities exist, command exits successfully and shows no entities message.
     */
    public function testExecuteWhenParameterBagGetThrowsUsesKernelAdapter(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $this->assertSame(0, $exitCode);
        $out = $output->fetch();
        $this->assertStringContainsString('No entities found with #[Anonymize]', $out);
        $this->assertStringContainsString('attribute', $out);
    }

    /**
     * Truncate without --force in non-interactive mode returns FAILURE (confirm default is no).
     */
    public function testExecuteTruncateWithoutForceInNonInteractiveReturnsFailure(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize(truncate: true)]
                class TruncateAbortTestEntity {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\TruncateAbortTestEntity';
        $tableName = 'truncate_abort_test';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn($tableName);

        $metadataDriver = $this->createMock(MappingDriver::class);
        $config         = $this->createMock(Configuration::class);
        $em             = $this->createMock(EntityManagerInterface::class);

        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(Result::class));

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with([$tableName])->willReturn(true);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Truncating tables', $out);
        $this->assertStringContainsString($tableName, $out);
        $this->assertStringContainsString('Truncate aborted', $out);
    }

    /**
     * Truncate with --force proceeds without interactive confirmation.
     */
    public function testExecuteTruncateWithForceProceedsWithoutConfirmation(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize(truncate: true)]
                class TruncateForceTestEntity {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\TruncateForceTestEntity';
        $tableName = 'truncate_force_test';

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->method('getTableName')->willReturn($tableName);
        $metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_NONE;

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn ($id): string => '`' . $id . '`');

        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(Result::class));
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_mysql']);
        $connection->method('executeStatement')->willReturn(0);

        $driver = $this->createMock(Driver::class);
        $connection->method('getDriver')->willReturn($driver);

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with([$tableName])->willReturn(true);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $metadataDriver = $this->createMock(MappingDriver::class);
        $config         = $this->createMock(Configuration::class);
        $em             = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--force' => true, '--no-progress' => true]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Truncating tables', $out);
        $this->assertStringContainsString('Truncated', $out);
        $this->assertStringNotContainsString('Truncate aborted', $out);
    }

    /**
     * Dry-run truncate does not require --force.
     */
    public function testExecuteTruncateDryRunDoesNotRequireForce(): void
    {
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Integration\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize(truncate: true)]
                class TruncateDryRunTestEntity {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Integration\Command\TruncateDryRunTestEntity';
        $tableName = 'truncate_dry_run_test';

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->method('getTableName')->willReturn($tableName);
        $metadata->inheritanceType = ClassMetadata::INHERITANCE_TYPE_NONE;

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn ($id): string => '`' . $id . '`');

        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(Result::class));
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('getParams')->willReturn(['driver' => 'pdo_mysql']);
        $connection->method('fetchOne')->willReturn('3');

        $driver = $this->createMock(Driver::class);
        $connection->method('getDriver')->willReturn($driver);

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->with([$tableName])->willReturn(true);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $metadataDriver = $this->createMock(MappingDriver::class);
        $config         = $this->createMock(Configuration::class);
        $em             = $this->createMock(EntityManagerInterface::class);
        $em->method('getConfiguration')->willReturn($config);
        $em->method('getConnection')->willReturn($connection);
        $config->method('getMetadataDriverImpl')->willReturn($metadataDriver);
        $metadataDriver->method('getAllClassNames')->willReturn([$className]);
        $em->method('getClassMetadata')->with($className)->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'doctrine.orm.default_entity_manager']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $command = $this->createDefaultAnonymizeCommand($doctrine);
        $input   = new ArrayInput(['--dry-run' => true, '--no-progress' => true]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('would be truncated', $out);
        $this->assertStringNotContainsString('Truncate aborted', $out);
    }

    private function createSafeEnvironmentProtection(): EnvironmentProtectionService
    {
        return new EnvironmentProtectionService(new ParameterBag([
            'kernel.environment' => 'dev',
            'kernel.project_dir' => $this->tempDir,
        ]), []);
    }

    private function createDefaultParameterBag(): ParameterBag
    {
        return new ParameterBag([
            'kernel.environment'              => 'dev',
            'kernel.debug'                    => true,
            'kernel.project_dir'              => $this->tempDir,
            'nowo_anonymize.stats_output_dir' => $this->tempDir . '/stats',
        ]);
    }

    /**
     * @param ContainerInterface|null $anonymizerRegistry optional plugin registry for custom anonymizer service ids
     */
    private function createDefaultAnonymizeCommand(
        ManagerRegistry $doctrine,
        ?ParameterBagInterface $parameterBag = null,
        ?AnonymizationHistoryService $historyService = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?ContainerInterface $anonymizerRegistry = null,
        ?EnvironmentProtectionService $environmentProtection = null
    ): AnonymizeCommand {
        $parameterBag ??= $this->createDefaultParameterBag();
        $historyService ??= new AnonymizationHistoryService($this->tempDir . '/history');
        $environmentProtection ??= $this->createSafeEnvironmentProtection();
        $fakerFactory     = new FakerFactory('en_US', $anonymizerRegistry);
        $patternMatcher   = new PatternMatcher();
        $anonymizeService = new AnonymizeService($fakerFactory, $patternMatcher, $eventDispatcher, $anonymizerRegistry);
        $preFlightCheck   = new PreFlightCheckService($fakerFactory);

        return new AnonymizeCommand(
            $anonymizeService,
            $preFlightCheck,
            $historyService,
            $environmentProtection,
            $doctrine,
            $parameterBag,
            $this->tempDir,
            $eventDispatcher,
        );
    }
}
