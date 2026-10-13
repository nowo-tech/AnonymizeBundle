<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Service;

use Nowo\AnonymizeBundle\Service\CommandRunnerInterface;

/**
 * Test double: records executed commands and, at exec time, the mode/content of the credential
 * files they reference (--defaults-extra-file, PGPASSFILE, --config); writes a fake dump file.
 *
 * @internal
 */
final class RecordingCommandRunner implements CommandRunnerInterface
{
    /** @var list<string> */
    public array $executed = [];

    /** @var array<string, array{mode: int, content: string}> */
    public array $credentialFiles = [];

    public function __construct(private readonly string $binary, private readonly int $exitCode, private readonly string $dumpContent)
    {
    }

    public function commandExists(string $command): bool
    {
        return $command === $this->binary;
    }

    /** @param-out list<string> $output */
    public function exec(string $command, ?array &$output = null): int
    {
        $this->executed[] = $command;
        if (preg_match_all("/(?:--defaults-extra-file=|PGPASSFILE=|--config=)'([^']*)'/", $command, $m) > 0) {
            foreach ($m[1] as $file) {
                clearstatcache();
                $this->credentialFiles[$file] = ['mode' => fileperms($file) & 0o777, 'content' => (string) file_get_contents($file)];
            }
        }
        if (preg_match("/(?:--result-file|--file)='([^']*)'/", $command, $m) === 1) {
            file_put_contents($m[1], $this->dumpContent);
        }
        $output = $this->exitCode === 0 ? [] : ['mysqldump: Got error: 1045: Access denied'];

        return $this->exitCode;
    }
}
