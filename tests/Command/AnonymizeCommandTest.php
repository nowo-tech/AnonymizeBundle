<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Command;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Nowo\AnonymizeBundle\Command\AnonymizeCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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
        // --entity has no shortcut (e would conflict with Symfony's global --env -e)
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
            namespace Nowo\AnonymizeBundle\Tests\Command {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class EntityFilterTestEntity {
                }
            }
        ');
        $className = 'Nowo\AnonymizeBundle\Tests\Command\EntityFilterTestEntity';

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
