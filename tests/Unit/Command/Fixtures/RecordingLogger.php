<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Command\Fixtures;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * In-memory PSR-3 logger for assertions.
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{0: mixed, 1: string, 2: array<mixed>}> */
    public array $records = [];

    /** @param array<mixed> $context */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [$level, (string) $message, $context];
    }
}
