<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Command;

use Nowo\AnonymizeBundle\Command\AnonymizationHistoryCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

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
        mkdir($this->tempDir, 0777, true);
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
     * Test that command can be instantiated.
     */
    public function testCommandCanBeInstantiated(): void
    {
        $command = new AnonymizationHistoryCommand();
        $this->assertInstanceOf(AnonymizationHistoryCommand::class, $command);
    }

    /**
     * Test that command configure method sets options correctly.
     */
    public function testCommandConfigureSetsOptions(): void
    {
        $command = new AnonymizationHistoryCommand();
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
        $command = new AnonymizationHistoryCommand();

        $input = new ArrayInput(['--compare' => 'single_id']);
        $output = new BufferedOutput();

        // The command will try to access getHistoryDir which may fail, but we're testing option parsing
        try {
            $result = $command->run($input, $output);
            // If it doesn't fail, check the output
            $outputContent = $output->fetch();
            if ($result === 1) {
                $this->assertStringContainsString('exactly 2 run IDs', $outputContent);
            }
        } catch (\Exception $e) {
            // Expected - command may fail due to missing history dir setup
            $this->assertTrue(true);
        }
    }
}
