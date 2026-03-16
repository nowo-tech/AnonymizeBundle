<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use const PHP_OS;

use function escapeshellarg;
use function proc_close;
use function proc_open;
use function stream_get_contents;

/**
 * Default implementation of CommandRunnerInterface that delegates to PHP's
 * process execution functions.
 *
 * An optional proc-open callable can be injected for testing (e.g. to simulate
 * proc_open failure without changing the environment).
 *
 * @phpstan-type ProcOpenCallable callable(string, array<int, array{0: string, 1: string}|array{0: string}, array<int, resource>): resource|false
 */
final class SystemCommandRunner implements CommandRunnerInterface
{
    /** @var ProcOpenCallable|null */
    private $procOpen;

    /**
     * @param ProcOpenCallable|null $procOpen Optional. When null, uses PHP's proc_open.
     */
    public function __construct(?callable $procOpen = null)
    {
        $this->procOpen = $procOpen;
    }

    public function commandExists(string $command): bool
    {
        $whereIsCommand = (PHP_OS === 'WINNT') ? 'where' : 'which';
        $cmd            = $whereIsCommand . ' ' . escapeshellarg($command);
        $descriptors    = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $pipes = [];

        $procOpen = $this->procOpen ?? static function (string $c, array $d, array &$p) {
            return proc_open($c, $d, $p);
        };
        $process = $procOpen($cmd, $descriptors, $pipes);

        if ($process === false) {
            return false;
        }

        $stdout     = stream_get_contents($pipes[1]);
        $returnCode = proc_close($process);

        return $returnCode === 0 && !empty($stdout);
    }

    public function exec(string $command, ?array &$output = null): int
    {
        $outputLines = [];
        $returnCode  = 0;

        exec($command, $outputLines, $returnCode);

        if ($output !== null) {
            $output = $outputLines;
        }

        return $returnCode;
    }
}

