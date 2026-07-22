<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

use function escapeshellarg;
use function explode;
use function in_array;
use function proc_close;
use function rtrim;
use function stream_get_contents;

use const PHP_OS;

/**
 * Default implementation of CommandRunnerInterface using Symfony Process.
 *
 * Applies hard and idle timeouts (REQ-RUNTIME-001) so FrankenPHP / FPM workers
 * are not left blocked by hung mysqldump / pg_dump / mongodump / compression tools.
 *
 * An optional proc-open callable can be injected for testing commandExists() only.
 */
final class SystemCommandRunner implements CommandRunnerInterface
{
    private const DEFAULT_TIMEOUT_SECONDS = 180.0;

    /** @var (callable(string, array, array): (false|resource))|null */
    private $procOpen;

    private readonly float $idleTimeoutSeconds;

    /**
     * @param (callable(string, array, array): (false|resource))|null $procOpen Optional. When null, uses Symfony Process for commandExists().
     * @param float $timeoutSeconds Wall-clock timeout for exec() (and idle timeout when idle is null)
     * @param float|null $idleTimeoutSeconds Idle timeout; defaults to the same as $timeoutSeconds
     */
    public function __construct(
        ?callable $procOpen = null,
        private readonly float $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
        ?float $idleTimeoutSeconds = null,
    ) {
        $this->procOpen           = $procOpen;
        $this->idleTimeoutSeconds = $idleTimeoutSeconds ?? $timeoutSeconds;
    }

    public function commandExists(string $command): bool
    {
        if ($this->procOpen !== null) {
            return $this->commandExistsViaProcOpen($command);
        }

        $whereIsCommand = (PHP_OS === 'WINNT') ? 'where' : 'which';
        $process        = Process::fromShellCommandline($whereIsCommand . ' ' . escapeshellarg($command));
        $process->setTimeout(10.0);
        $process->setIdleTimeout(10.0);

        try {
            $process->run();
            // @codeCoverageIgnoreStart
        } catch (ProcessTimedOutException) {
            // Defensive: which/where should not hang; stop if the OS misbehaves.
            $process->stop(0);

            return false;
        }
        // @codeCoverageIgnoreEnd

        $stdout = $process->getOutput();

        return $process->isSuccessful() && !in_array($stdout, ['', '0'], true);
    }

    public function exec(string $command, ?array &$output = null): int
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout($this->timeoutSeconds);
        $process->setIdleTimeout($this->idleTimeoutSeconds);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            $process->stop(0);
            if ($output !== null) {
                $output = ['Command timed out after ' . $this->timeoutSeconds . ' seconds'];
            }

            return 124;
        }

        if ($output !== null) {
            $stdout = $process->getOutput();
            $stderr = $process->getErrorOutput();
            $merged = $stdout . ($stderr !== '' ? "\n" . $stderr : '');
            $output = $merged === '' ? [] : explode("\n", rtrim($merged, "\n"));
        }

        return $process->getExitCode() ?? 1;
    }

    private function commandExistsViaProcOpen(string $command): bool
    {
        $whereIsCommand = (PHP_OS === 'WINNT') ? 'where' : 'which';
        $cmd            = $whereIsCommand . ' ' . escapeshellarg($command);
        $descriptors    = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $pipes = [];

        $procOpen = $this->procOpen;
        $process  = $procOpen($cmd, $descriptors, $pipes);

        if ($process === false) {
            return false;
        }

        $stdout     = stream_get_contents($pipes[1]);
        $returnCode = proc_close($process);

        return $returnCode === 0 && !in_array($stdout, ['', '0', false], true);
    }
}
