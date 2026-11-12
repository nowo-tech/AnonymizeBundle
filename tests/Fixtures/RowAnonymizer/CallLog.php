<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Fixtures\RowAnonymizer;

/**
 * What a fixture anonymizer was called with and returned, per call (test-only, one instance per run).
 */
final class CallLog
{
    /** @var list<array{method: string, table: string, dryRun: bool, input: array<mixed>, output: array<mixed>}> */
    public array $calls = [];

    /**
     * @param array<mixed> $input
     * @param array<mixed> $output
     *
     * @return array<mixed>
     */
    public function record(string $method, string $table, bool $dryRun, array $input, array $output): array
    {
        $this->calls[] = ['method' => $method, 'table' => $table, 'dryRun' => $dryRun, 'input' => $input, 'output' => $output];

        return $output;
    }
}
