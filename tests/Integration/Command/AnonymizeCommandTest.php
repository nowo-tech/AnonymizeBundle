<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Command;

use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Nowo\AnonymizeBundle\Command\AnonymizeCommand;
use Nowo\AnonymizeBundle\Event\BeforeAnonymizeEvent;
use Nowo\AnonymizeBundle\Service\EntityAnonymizerServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
        $container = $this->createContainerWithSafeEnvironment();
        $command   = new AnonymizeCommand($container);

        $this->assertSame('nowo:anonymize:run', $command->getName());
        $this->assertStringContainsString('Anonymize database records', $command->getDescription());
    }

    /**
     * Test that configure defines expected options.
     */
    public function testConfigureDefinesOptions(): void
    {
        $container  = $this->createContainerWithSafeEnvironment();
        $command    = new AnonymizeCommand($container);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
        $input   = new ArrayInput(['--entity' => ['App\Entity\SmsNotification']]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('No entities matching --entity filter in manager "default"', $output->fetch());
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

        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
        $input   = new ArrayInput(['--entity' => ['App\Entity\NonExistentEntity']]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(0, $exitCode);
        $out = $output->fetch();
        $this->assertStringContainsString('No entities matching --entity filter in manager "default"', $out);
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

        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);
        $container->set('event_dispatcher', $eventDispatcher);

        $command = new AnonymizeCommand($container);
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

        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
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

        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
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

        $platform = $this->createMock(\Doctrine\DBAL\Platforms\AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn ($id) => '`' . $id . '`');

        $record = ['id' => 1];
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(\Doctrine\DBAL\Result::class));
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('quote')->willReturnCallback(static fn ($v) => "'" . str_replace("'", "''", (string) $v) . "'");
        $connection->method('fetchOne')->willReturn('1');
        $fetchCallCount = 0;
        $connection->method('fetchAllAssociative')->willReturnCallback(function () use ($record, &$fetchCallCount) {
            $fetchCallCount++;

            return $fetchCallCount === 1 ? [$record] : [];
        });
        $connection->method('beginTransaction')->willReturnCallback(static function (): void {});
        $connection->method('commit')->willReturnCallback(static function (): void {});

        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);
        $container->set('custom_anonymizer', $anonymizer);

        $command = new AnonymizeCommand($container);
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

        $platform = $this->createMock(\Doctrine\DBAL\Platforms\AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn ($id) => '`' . $id . '`');

        $record = ['id' => 1];
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(\Doctrine\DBAL\Result::class));
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('quote')->willReturnCallback(static fn ($v) => "'" . str_replace("'", "''", (string) $v) . "'");
        $connection->method('fetchOne')->willReturn('1');
        $fetchCallCount = 0;
        $connection->method('fetchAllAssociative')->willReturnCallback(function () use ($record, &$fetchCallCount) {
            $fetchCallCount++;

            return $fetchCallCount === 1 ? [$record] : [];
        });
        $connection->method('beginTransaction')->willReturnCallback(static function (): void {});
        $connection->method('commit')->willReturnCallback(static function (): void {});

        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);
        $container->set('custom_anonymizer', $anonymizer);

        $command = new AnonymizeCommand($container);
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
            ['id', new \Doctrine\ORM\Mapping\FieldMapping('id', 'integer', 'id')],
            ['email', new \Doctrine\ORM\Mapping\FieldMapping('email', 'string', 'email')],
        ]);

        $platform = $this->createMock(\Doctrine\DBAL\Platforms\AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->willReturnCallback(static fn ($id) => '`' . $id . '`');

        $record = ['id' => 1, 'email' => 'user@example.com'];
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(\Doctrine\DBAL\Result::class));
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $connection->method('quote')->willReturnCallback(static fn ($v) => "'" . str_replace("'", "''", (string) $v) . "'");
        $connection->method('fetchOne')->willReturn('1');
        $fetchCallCount = 0;
        $connection->method('fetchAllAssociative')->willReturnCallback(function () use ($record, &$fetchCallCount) {
            $fetchCallCount++;

            return $fetchCallCount === 1 ? [$record] : [];
        });
        $connection->method('beginTransaction')->willReturnCallback(static function (): void {});
        $connection->method('commit')->willReturnCallback(static function (): void {});
        $connection->method('executeStatement')->willReturn(1);

        $idColumn = $this->createMock(Column::class);
        $idColumn->method('getName')->willReturn('id');
        $emailColumn = $this->createMock(Column::class);
        $emailColumn->method('getName')->willReturn('email');

        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
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

        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $connection->method('executeQuery')->with('SELECT 1')->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
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
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturnCallback(function (string $key) {
            return match ($key) {
                'kernel.environment' => 'prod',
                'kernel.debug'       => false,
                'kernel.project_dir' => $this->tempDir,
                default              => null,
            };
        });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn (string $id) => $id === 'parameter_bag');
        $container->method('get')->willReturnCallback(static fn (string $id) => $id === 'parameter_bag' ? $parameterBag : null);

        $command = new AnonymizeCommand($container);
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

        $container = $this->createContainerWithSafeEnvironment();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(0, $exitCode);
        $out = $output->fetch();
        $this->assertStringContainsString('No entities found with #[Anonymize] attribute', $out);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
        $input   = new ArrayInput(['--connection' => ['mongodb']]);
        $output  = new BufferedOutput();

        $command->run($input, $output);

        $this->assertStringContainsString('MongoDB ODM support is not yet available', $output->fetch());
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
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
        $em = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default', 'other' => 'other']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
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
        $doctrine->method('getManager')->with('default')->willThrowException(new \Exception('Connection failed'));

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Error processing entity manager', $out);
    }

    /**
     * Test that --stats-json with absolute path exports to that path without prepending stats_output_dir (covers branch when path starts with / or contains \).
     */
    public function testExecuteWithStatsJsonAbsolutePathExportsToGivenPath(): void
    {
        $absoluteJsonPath = $this->tempDir . '/absolute_run_stats.json';

        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
        $input   = new ArrayInput(['--stats-json' => $absoluteJsonPath]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Statistics exported to JSON', $out);
        $this->assertFileExists($absoluteJsonPath);
        $data = json_decode(file_get_contents($absoluteJsonPath), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('global', $data);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
        $input   = new ArrayInput(['--stats-json' => 'run_stats.json']);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Statistics exported to JSON', $out);
        // Default stats_output_dir is %kernel.project_dir%/var/stats when param not set
        $statsDir = $this->tempDir . '/var/stats';
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
        $input   = new ArrayInput(['--stats-csv' => 'run_stats.csv']);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Statistics exported to CSV', $out);
        $csvPath = $this->tempDir . '/var/stats/run_stats.csv';
        $this->assertFileExists($csvPath);
        $this->assertStringContainsString('Total Processed', file_get_contents($csvPath));
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
        $input   = new ArrayInput(['--connection' => ['default', 'mongodb']]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('MongoDB ODM support is not yet available', $out);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
        $input   = new ArrayInput(['--interactive' => true]);
        if ($input instanceof StreamableInputInterface) {
            $input->setStream(fopen('data://text/plain,no', 'r'));
        }
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $command = new AnonymizeCommand($container);
        $input   = new ArrayInput(['--interactive' => true]);
        if ($input instanceof StreamableInputInterface) {
            $input->setStream(fopen('data://text/plain,y', 'r'));
        }
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

        $container = $this->createContainerWithSafeEnvironmentAndKernel();
        $container->set('doctrine', $doctrine);

        $historyDirPath = $this->tempDir . '/history';
        file_put_contents($historyDirPath, '');
        $container->get('parameter_bag')->set('nowo_anonymize.history_dir', $historyDirPath);

        $command = new AnonymizeCommand($container);
        $input   = new ArrayInput(['--debug' => true]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $out      = $output->fetch();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Failed to save history', $out);
        $this->assertStringContainsString('[DEBUG]', $out);
    }

    /**
     * Test that when container has parameter_bag but get('parameter_bag') throws, command uses KernelParameterBagAdapter and still runs.
     */
    public function testExecuteWhenParameterBagGetThrowsUsesKernelAdapter(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManagerNames')->willReturn(['default' => 'default']);
        $doctrine->method('getManager')->with('default')->willReturn($em);

        $tempDir = $this->tempDir;
        $container = new class($doctrine, $tempDir) implements ContainerInterface {
            public function __construct(
                private ManagerRegistry $doctrine,
                private string $tempDir
            ) {
            }

            public function get(string $id): mixed
            {
                if ($id === 'doctrine') {
                    return $this->doctrine;
                }
                if ($id === 'parameter_bag') {
                    throw new \Exception('parameter_bag unavailable');
                }
                return null;
            }

            public function has(string $id): bool
            {
                return $id === 'parameter_bag' || $id === 'doctrine';
            }

            public function hasParameter(string $name): bool
            {
                return in_array($name, ['kernel.environment', 'kernel.debug', 'kernel.project_dir'], true);
            }

            public function getParameter(string $name): mixed
            {
                return match ($name) {
                    'kernel.environment' => 'dev',
                    'kernel.debug' => true,
                    'kernel.project_dir' => $this->tempDir,
                    default => null,
                };
            }
        };

        $command = new AnonymizeCommand($container);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $exitCode = $command->run($input, $output);
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('No entities found with #[Anonymize] attribute', $output->fetch());
    }

    private function createContainerWithSafeEnvironment(): ContainerBuilder
    {
        $parameterBag = new ParameterBag([
            'kernel.environment' => 'dev',
            'kernel.debug'       => true,
            'kernel.project_dir' => $this->tempDir,
        ]);

        $container = new ContainerBuilder($parameterBag);
        $container->set('parameter_bag', $parameterBag);

        return $container;
    }

    private function createContainerWithSafeEnvironmentAndKernel(): ContainerBuilder
    {
        $container = $this->createContainerWithSafeEnvironment();

        $innerContainer = new ContainerBuilder(new ParameterBag([
            'nowo_anonymize.history_dir'      => $this->tempDir . '/history',
            'nowo_anonymize.stats_output_dir' => $this->tempDir . '/stats',
        ]));
        $innerContainer->setParameter('nowo_anonymize.history_dir', $this->tempDir . '/history');
        $innerContainer->setParameter('nowo_anonymize.stats_output_dir', $this->tempDir . '/stats');

        $kernel = new class($innerContainer, $this->tempDir) {
            public function __construct(
                public ContainerBuilder $container,
                private string $projectDir
            ) {
            }

            public function getProjectDir(): string
            {
                return $this->projectDir;
            }
        };

        $container->set('kernel', $kernel);

        return $container;
    }
}
