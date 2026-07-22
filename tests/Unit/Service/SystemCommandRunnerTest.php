<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Service;

use Nowo\AnonymizeBundle\Service\SystemCommandRunner;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SystemCommandRunner.
 */
class SystemCommandRunnerTest extends TestCase
{
    private SystemCommandRunner $runner;

    protected function setUp(): void
    {
        $this->runner = new SystemCommandRunner();
    }

    /**
     * commandExists returns true for a command on PATH (e.g. "php").
     */
    public function testCommandExistsReturnsTrueForPhp(): void
    {
        $result = $this->runner->commandExists('php');
        $this->assertTrue($result);
    }

    /**
     * commandExists returns false when proc_open fails (e.g. process creation failure).
     */
    public function testCommandExistsReturnsFalseWhenProcOpenFails(): void
    {
        $runner = new SystemCommandRunner(static fn (): bool => false);
        $result = $runner->commandExists('php');
        $this->assertFalse($result);
    }

    /**
     * commandExists can use an injected proc_open callable (legacy test hook).
     */
    public function testCommandExistsViaInjectedProcOpenSuccess(): void
    {
        $runner = new SystemCommandRunner(
            static fn (string $command, array $descriptors, array &$pipes) => proc_open($command, $descriptors, $pipes),
        );

        $this->assertTrue($runner->commandExists('php'));
    }

    /**
     * commandExists returns false for a nonexistent command.
     */
    public function testCommandExistsReturnsFalseForNonexistentCommand(): void
    {
        $result = $this->runner->commandExists('nonexistent_command_xyz_' . uniqid());
        $this->assertFalse($result);
    }

    /**
     * exec returns exit code 0 for a successful command.
     */
    public function testExecReturnsZeroForSuccess(): void
    {
        $code = $this->runner->exec('php -r "exit(0);"');
        $this->assertSame(0, $code);
    }

    /**
     * exec returns non-zero for a failing command.
     */
    public function testExecReturnsNonZeroForFailure(): void
    {
        $code = $this->runner->exec('php -r "exit(2);"');
        $this->assertSame(2, $code);
    }

    /**
     * exec populates output array when passed by reference.
     */
    public function testExecPopulatesOutputWhenPassed(): void
    {
        $output = [];
        $this->runner->exec('php -r "echo \"hello\";"', $output);
        $this->assertIsArray($output);
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('hello', implode('', $output));
    }

    /**
     * exec can be called without output argument (default null).
     */
    public function testExecWithoutOutputArgument(): void
    {
        $code = $this->runner->exec('php -r "exit(0);"');
        $this->assertSame(0, $code);
    }

    /**
     * exec stops a hung command when the timeout elapses (REQ-RUNTIME-001).
     */
    public function testExecTimesOutAndStopsProcess(): void
    {
        $runner = new SystemCommandRunner(null, 1.0, 1.0);
        $output = [];
        $code   = $runner->exec('php -r "sleep(10);"', $output);

        $this->assertSame(124, $code);
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('timed out', implode(' ', $output));
    }
}
