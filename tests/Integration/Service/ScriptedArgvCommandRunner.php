<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Service;

use Nowo\AnonymizeBundle\Service\ArgvCommandRunnerInterface;

/**
 * Test double: argv runner whose tools are scripted per program name; records every call.
 *
 * @internal
 */
final class ScriptedArgvCommandRunner implements ArgvCommandRunnerInterface
{
    /** @var list<array{list<string>, array<string, string>}> */
    public array $calls = [];

    /**
     * @param array<string, callable(list<string>, array<string, string>): int> $scripts
     */
    public function __construct(private readonly array $scripts)
    {
    }

    public function commandExists(string $command): bool
    {
        return isset($this->scripts[$command]);
    }

    /** @param-out list<string> $output */
    public function exec(string $command, ?array &$output = null): int
    {
        $output = [];

        return 127;
    }

    /** @param-out list<string> $output */
    public function run(array $argv, array $env = [], ?array &$output = null): int
    {
        $this->calls[] = [$argv, $env];
        $output        = [];

        return ($this->scripts[$argv[0]])($argv, $env);
    }
}
