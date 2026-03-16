<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Command;

use Nowo\AnonymizeBundle\Command\AnonymizationHistoryCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Test case for AnonymizationHistoryCommand.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizationHistoryCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/anonymize_history_test_' . uniqid();
        mkdir($this->tempDir, 0o777, true);
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

    private function createContainer(): ContainerInterface
    {
        $parameterBag = new ParameterBag([
            'nowo_anonymize.history_dir' => $this->tempDir,
        ]);

        $container = new ContainerBuilder($parameterBag);
        $container->set('parameter_bag', $parameterBag);

        return $container;
    }

    /**
     * Test that command can be instantiated.
     */
    public function testCommandCanBeInstantiated(): void
    {
        $command = new AnonymizationHistoryCommand($this->createContainer());
        $this->assertInstanceOf(AnonymizationHistoryCommand::class, $command);
    }

    /**
     * Test that command configure method sets options correctly.
     */
    public function testCommandConfigureSetsOptions(): void
    {
        $command    = new AnonymizationHistoryCommand($this->createContainer());
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('limit'));
        $this->assertTrue($definition->hasOption('connection'));
        $this->assertTrue($definition->hasOption('run-id'));
        $this->assertTrue($definition->hasOption('compare'));
        $this->assertTrue($definition->hasOption('cleanup'));
        $this->assertTrue($definition->hasOption('days'));
        $this->assertTrue($definition->hasOption('json'));
    }

    /**
     * Test that command handles compare option with invalid input.
     */
    public function testCommandHandlesCompareOptionWithInvalidInput(): void
    {
        $command = new AnonymizationHistoryCommand($this->createContainer());

        $input  = new ArrayInput(['--compare' => 'single_id']);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);
        $this->assertSame(1, $result);
        $this->assertStringContainsString('exactly 2 run IDs', $output->fetch());
    }

    /**
     * Test execute lists runs (empty list).
     */
    public function testExecuteListsRunsEmpty(): void
    {
        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);
        $this->assertSame(0, $result);
        $content = $output->fetch();
        $this->assertStringContainsString('history', strtolower($content));
    }

    /**
     * Test execute with --cleanup.
     */
    public function testExecuteCleanup(): void
    {
        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput(['--cleanup' => true, '--days' => '30']);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);
        $this->assertSame(0, $result);
        $this->assertStringContainsString('Cleaned up', $output->fetch());
    }

    /**
     * Test execute with --limit.
     */
    public function testExecuteWithLimit(): void
    {
        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput(['--limit' => '5']);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);
        $this->assertSame(0, $result);
    }

    /**
     * Test execute with --run-id when no runs exist.
     */
    public function testExecuteRunIdNotFound(): void
    {
        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput(['--run-id' => 'nonexistent-id-123']);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);
        $this->assertSame(1, $result);
        $this->assertStringContainsString('not found', $output->fetch());
    }

    /**
     * Test execute with --compare when runs not found.
     */
    public function testExecuteCompareRunsNotFound(): void
    {
        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput(['--compare' => 'id1,id2']);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);
        $this->assertSame(1, $result);
        $this->assertStringContainsString('not found', $output->fetch());
    }

    /**
     * Test execute with --json and no runs.
     */
    public function testExecuteJsonOutput(): void
    {
        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput(['--json' => true]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);
        $this->assertSame(0, $result);
        $content = $output->fetch();
        $this->assertTrue($content === '' || $content === '[]' || str_starts_with(trim($content), '['));
    }

    /**
     * Test execute with --run-id when a run exists (view details).
     */
    public function testExecuteRunIdFound(): void
    {
        $historyService = new \Nowo\AnonymizeBundle\Service\AnonymizationHistoryService($this->tempDir);
        $stats          = ['global' => ['total_processed' => 10, 'total_updated' => 8], 'entities' => []];
        $path           = $historyService->saveRun($stats, ['connection' => 'default']);
        $content        = json_decode(file_get_contents($path), true);
        $runId          = $content['id'] ?? '';

        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput(['--run-id' => $runId]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);
        $this->assertSame(0, $result);
        $this->assertStringContainsString($runId, $output->fetch());
    }

    /**
     * Test execute with --run-id when run has entity statistics (covers displayRun Entity Statistics section).
     */
    public function testExecuteRunIdWithEntityStatisticsDisplaysEntitySection(): void
    {
        $historyService = new \Nowo\AnonymizeBundle\Service\AnonymizationHistoryService($this->tempDir);
        $stats = [
            'global'  => ['total_entities' => 1, 'total_processed' => 10, 'total_updated' => 8, 'total_skipped' => 2, 'duration' => 1.0, 'start_time' => time(), 'end_time' => time()],
            'entities' => [
                'App\Entity\User@default' => [
                    'entity'    => 'App\Entity\User',
                    'connection' => 'default',
                    'processed'  => 10,
                    'updated'    => 8,
                    'skipped'    => 2,
                    'properties' => ['email' => 8],
                ],
            ],
        ];
        $path  = $historyService->saveRun($stats, ['connection' => 'default']);
        $runId = json_decode(file_get_contents($path), true)['id'] ?? '';

        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput(['--run-id' => $runId]);
        $output  = new BufferedOutput();

        $result  = $command->run($input, $output);
        $content = $output->fetch();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('Entity Statistics', $content);
        $this->assertStringContainsString('App\\Entity\\User', $content);
        $this->assertStringContainsString('Success Rate', $content);
    }

    /**
     * Test execute with --compare when two runs exist.
     */
    public function testExecuteCompareTwoRuns(): void
    {
        $historyService = new \Nowo\AnonymizeBundle\Service\AnonymizationHistoryService($this->tempDir);
        $path1          = $historyService->saveRun(['global' => ['total_processed' => 10], 'entities' => []], []);
        $path2          = $historyService->saveRun(['global' => ['total_processed' => 20], 'entities' => []], []);
        $id1            = json_decode(file_get_contents($path1), true)['id'] ?? '';
        $id2            = json_decode(file_get_contents($path2), true)['id'] ?? '';

        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput(['--compare' => $id1 . ',' . $id2]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);
        $this->assertSame(0, $result);
    }

    /**
     * Test execute lists runs and displays table (covers displayRunsList).
     */
    public function testExecuteListsRunsWithRunsDisplaysTable(): void
    {
        $historyService = new \Nowo\AnonymizeBundle\Service\AnonymizationHistoryService($this->tempDir);
        $historyService->saveRun(
            ['global' => ['total_entities' => 2, 'total_processed' => 100, 'total_updated' => 98, 'duration' => 1.5], 'entities' => []],
            [],
        );

        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);
        $content = $output->fetch();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('Anonymization History', $content);
        $this->assertStringContainsString('Run ID', $content);
        $this->assertStringContainsString('Date/Time', $content);
        $this->assertStringContainsString('Entities', $content);
        $this->assertStringContainsString('Processed', $content);
        $this->assertStringContainsString('Updated', $content);
        $this->assertStringContainsString('Duration', $content);
        $this->assertStringContainsString('--run-id', $content);
    }

    /**
     * Test execute with --limit when runs exist (covers limit path and displayRunsList).
     */
    public function testExecuteWithLimitWhenRunsExist(): void
    {
        $historyService = new \Nowo\AnonymizeBundle\Service\AnonymizationHistoryService($this->tempDir);
        $historyService->saveRun(['global' => ['total_processed' => 5], 'entities' => []], []);
        $historyService->saveRun(['global' => ['total_processed' => 10], 'entities' => []], []);

        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput(['--limit' => '1']);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);
        $content = $output->fetch();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('Anonymization History', $content);
    }

    /**
     * Test execute with --run-id and --json when run exists.
     */
    public function testExecuteRunIdWithJsonOutput(): void
    {
        $historyService = new \Nowo\AnonymizeBundle\Service\AnonymizationHistoryService($this->tempDir);
        $path   = $historyService->saveRun(['global' => ['total_processed' => 10], 'entities' => []], []);
        $runId  = json_decode(file_get_contents($path), true)['id'] ?? '';

        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput(['--run-id' => $runId, '--json' => true]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);
        $content = $output->fetch();

        $this->assertSame(0, $result);
        $this->assertStringContainsString($runId, $content);
        $this->assertTrue(str_contains($content, '"id"') && str_contains($content, '"statistics"'));
    }

    /**
     * Test that getHistoryDir uses NOWO_ANONYMIZE_HISTORY_DIR when container has no parameter_bag.
     */
    public function testGetHistoryDirFromEnvWhenNoParameterBag(): void
    {
        $envDir = sys_get_temp_dir() . '/anonymize_env_' . uniqid();
        mkdir($envDir, 0o777, true);

        $backup = $_ENV['NOWO_ANONYMIZE_HISTORY_DIR'] ?? null;
        $_ENV['NOWO_ANONYMIZE_HISTORY_DIR'] = $envDir;

        try {
            $historyService = new \Nowo\AnonymizeBundle\Service\AnonymizationHistoryService($envDir);
            $historyService->saveRun(['global' => ['total_processed' => 3], 'entities' => []], []);

            $container = $this->createMock(ContainerInterface::class);
            $container->method('has')->with('parameter_bag')->willReturn(false);
            $command = new AnonymizationHistoryCommand($container);
            $input   = new ArrayInput([]);
            $output  = new BufferedOutput();

            $result = $command->run($input, $output);
            $content = $output->fetch();

            $this->assertSame(0, $result);
            $this->assertStringContainsString('Anonymization History', $content);
        } finally {
            if ($backup !== null) {
                $_ENV['NOWO_ANONYMIZE_HISTORY_DIR'] = $backup;
            } else {
                unset($_ENV['NOWO_ANONYMIZE_HISTORY_DIR']);
            }
            $this->removeDirectory($envDir);
        }
    }

    /**
     * Test that getHistoryDir resolves %kernel.project_dir% via getcwd() when container has no hasParameter (getProjectDirFromContainer fallback).
     */
    public function testGetHistoryDirResolvesKernelProjectDirViaGetcwdWhenContainerHasNoParameter(): void
    {
        $suffix     = 'cwd_' . uniqid();
        $historyDir = getcwd() . '/var/' . $suffix;
        mkdir($historyDir, 0o777, true);

        $backup = $_ENV['NOWO_ANONYMIZE_HISTORY_DIR'] ?? null;
        $_ENV['NOWO_ANONYMIZE_HISTORY_DIR'] = '%kernel.project_dir%/var/' . $suffix;

        try {
            $historyService = new \Nowo\AnonymizeBundle\Service\AnonymizationHistoryService($historyDir);
            $historyService->saveRun(['global' => ['total_processed' => 1], 'entities' => []], []);

            $container = $this->createMock(ContainerInterface::class);
            $container->method('has')->with('parameter_bag')->willReturn(false);
            $command = new AnonymizationHistoryCommand($container);
            $input   = new ArrayInput([]);
            $output  = new BufferedOutput();

            $result = $command->run($input, $output);
            $content = $output->fetch();

            $this->assertSame(0, $result);
            $this->assertStringContainsString('Anonymization History', $content);
        } finally {
            if ($backup !== null) {
                $_ENV['NOWO_ANONYMIZE_HISTORY_DIR'] = $backup;
            } else {
                unset($_ENV['NOWO_ANONYMIZE_HISTORY_DIR']);
            }
            $this->removeDirectory($historyDir);
        }
    }

    /**
     * Test that getHistoryDir resolves %kernel.project_dir% via getProjectDirFromContainer.
     */
    public function testGetHistoryDirResolvesKernelProjectDir(): void
    {
        $projectDir = $this->tempDir . '/project';
        $historySubDir = $projectDir . '/var/custom_history';
        mkdir($historySubDir, 0o777, true);

        $parameterBag = new ParameterBag([
            'nowo_anonymize.history_dir' => '%kernel.project_dir%/var/custom_history',
        ]);
        $container = new ContainerBuilder($parameterBag);
        $container->set('parameter_bag', $parameterBag);
        $container->setParameter('kernel.project_dir', $projectDir);
        $container->compile(false);

        $historyService = new \Nowo\AnonymizeBundle\Service\AnonymizationHistoryService($historySubDir);
        $historyService->saveRun(['global' => ['total_processed' => 7], 'entities' => []], []);

        $command = new AnonymizationHistoryCommand($container);
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $result = $command->run($input, $output);
        $content = $output->fetch();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('Anonymization History', $content);
    }

    /**
     * Test formatDuration via reflection: ms, seconds, minutes+seconds.
     */
    public function testFormatDurationFormatsCorrectly(): void
    {
        $command = new AnonymizationHistoryCommand($this->createContainer());
        $ref = new \ReflectionClass($command);
        $method = $ref->getMethod('formatDuration');
        $method->setAccessible(true);

        $this->assertStringEndsWith('ms', $method->invoke($command, 0.0));
        $this->assertStringEndsWith('ms', $method->invoke($command, 0.5));
        $this->assertStringEndsWith('s', $method->invoke($command, 30.0));
        $this->assertMatchesRegularExpression('/\d+ m .* s/', $method->invoke($command, 90.0));
        $this->assertMatchesRegularExpression('/\d+ m .* s/', $method->invoke($command, 3700.0));
    }

    /**
     * Test that --run-id with non-existent ID returns failure and error message (covers lines 115-117).
     */
    public function testExecuteRunIdNotFoundReturnsFailure(): void
    {
        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput(['--run-id' => 'nonexistent-id']);
        $output  = new BufferedOutput();

        $result  = $command->run($input, $output);
        $content = $output->fetch();

        $this->assertSame(1, $result);
        $this->assertStringContainsString('not found', $content);
        $this->assertStringContainsString('nonexistent-id', $content);
    }

    /**
     * Test that when no runs exist in history, command shows info message and exits success (covers lines 157-159).
     */
    public function testExecuteShowsInfoWhenNoRunsInHistory(): void
    {
        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput([]);
        $output  = new BufferedOutput();

        $result  = $command->run($input, $output);
        $content = $output->fetch();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('No anonymization runs found in history', $content);
    }

    /**
     * Test that displayRun shows "N/A" success rate when entity has processed 0 (covers line 242).
     */
    public function testDisplayRunShowsNASuccessRateWhenProcessedZero(): void
    {
        $historyService = new \Nowo\AnonymizeBundle\Service\AnonymizationHistoryService($this->tempDir);
        $stats = [
            'global'   => ['total_processed' => 0, 'duration' => 0],
            'entities' => [
                'App\Entity\EmptyRun@default' => [
                    'entity'     => 'App\Entity\EmptyRun',
                    'connection' => 'default',
                    'processed'  => 0,
                    'updated'    => 0,
                    'skipped'    => 0,
                ],
            ],
        ];
        $path  = $historyService->saveRun($stats, []);
        $runId = json_decode(file_get_contents($path), true)['id'] ?? '';

        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput(['--run-id' => $runId]);
        $output  = new BufferedOutput();

        $result  = $command->run($input, $output);
        $content = $output->fetch();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('Entity Statistics', $content);
        $this->assertStringContainsString('Success Rate', $content);
        $this->assertStringContainsString('N/A', $content);
    }

    /**
     * Test that --compare with two runs that have entity stats displays "Entity Statistics Comparison" (covers lines 301-319).
     */
    public function testExecuteCompareWithEntitiesDisplaysEntityComparisonSection(): void
    {
        $historyService = new \Nowo\AnonymizeBundle\Service\AnonymizationHistoryService($this->tempDir);
        $entities1 = [
            'App\Entity\User@default' => [
                'entity'     => 'App\Entity\User',
                'connection' => 'default',
                'processed'  => 10,
                'updated'    => 8,
                'skipped'    => 2,
            ],
        ];
        $entities2 = [
            'App\Entity\User@default' => [
                'entity'     => 'App\Entity\User',
                'connection' => 'default',
                'processed'  => 15,
                'updated'    => 12,
                'skipped'    => 3,
            ],
        ];
        $path1 = $historyService->saveRun(['global' => ['total_processed' => 10], 'entities' => $entities1], []);
        $path2 = $historyService->saveRun(['global' => ['total_processed' => 15], 'entities' => $entities2], []);
        $id1   = json_decode(file_get_contents($path1), true)['id'] ?? '';
        $id2   = json_decode(file_get_contents($path2), true)['id'] ?? '';

        $command = new AnonymizationHistoryCommand($this->createContainer());
        $input   = new ArrayInput(['--compare' => $id1 . ',' . $id2]);
        $output  = new BufferedOutput();

        $result  = $command->run($input, $output);
        $content = $output->fetch();

        $this->assertSame(0, $result);
        $this->assertStringContainsString('Run Comparison', $content);
        $this->assertStringContainsString('Entity Statistics Comparison', $content);
        $this->assertStringContainsString('App\\Entity\\User', $content);
    }

    /**
     * Test that getHistoryDir falls back to ENV when container has parameter_bag but get() throws (covers line 362 catch).
     */
    public function testGetHistoryDirFallsBackToEnvWhenParameterBagGetThrows(): void
    {
        $envDir = sys_get_temp_dir() . '/anonymize_throw_' . uniqid();
        mkdir($envDir, 0o777, true);

        $backup = $_ENV['NOWO_ANONYMIZE_HISTORY_DIR'] ?? null;
        $_ENV['NOWO_ANONYMIZE_HISTORY_DIR'] = $envDir;

        try {
            $historyService = new \Nowo\AnonymizeBundle\Service\AnonymizationHistoryService($envDir);
            $historyService->saveRun(['global' => ['total_processed' => 1], 'entities' => []], []);

            $container = $this->createMock(ContainerInterface::class);
            $container->method('has')->with('parameter_bag')->willReturn(true);
            $container->method('get')->with('parameter_bag')->willThrowException(new \RuntimeException('parameter_bag unavailable'));

            $command = new AnonymizationHistoryCommand($container);
            $input   = new ArrayInput([]);
            $output  = new BufferedOutput();

            $result  = $command->run($input, $output);
            $content = $output->fetch();

            $this->assertSame(0, $result);
            $this->assertStringContainsString('Anonymization History', $content);
        } finally {
            if ($backup !== null) {
                $_ENV['NOWO_ANONYMIZE_HISTORY_DIR'] = $backup;
            } else {
                unset($_ENV['NOWO_ANONYMIZE_HISTORY_DIR']);
            }
            $this->removeDirectory($envDir);
        }
    }
}
