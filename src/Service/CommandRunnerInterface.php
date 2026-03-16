<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

/**
 * Abstraction for executing shell commands.
 *
 * This allows database export logic to be tested deterministically
 * without depending on real binaries or the host environment.
 */
interface CommandRunnerInterface
{
    /**
     * Checks if a command exists in the current environment.
     *
     * @param string $command The command name (e.g. "mysqldump")
     *
     * @return bool True if the command exists, false otherwise
     */
    public function commandExists(string $command): bool;

    /**
     * Executes a shell command.
     *
     * @param string      $command The full command string to execute
     * @param array<string>|null $output  Captured stdout lines (if any)
     *
     * @return int The process exit code (0 on success)
     */
    public function exec(string $command, ?array &$output = null): int;
}

