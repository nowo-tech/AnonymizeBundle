<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Command;

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
}
