<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

/**
 * Command runner that executes an argument vector directly, without a shell.
 *
 * Preferred by DatabaseExportService: no shell parsing of host, user, database or path values, and
 * environment variables (e.g. PGPASSFILE) are passed to the child process instead of a command prefix.
 * Runners that only implement CommandRunnerInterface receive an escaped command string instead.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface ArgvCommandRunnerInterface extends CommandRunnerInterface
{
    /**
     * Runs a program with its arguments (no shell).
     *
     * @param list<string> $argv Program followed by its arguments
     * @param array<string, string> $env Extra environment variables for the child process
     * @param array<string>|null $output Captured stdout + stderr lines (if any)
     *
     * @return int The process exit code (0 on success)
     */
    public function run(array $argv, array $env = [], ?array &$output = null): int;
}
