<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Command;

use Nowo\AnonymizeBundle\Command\AbstractCommand;
use Nowo\AnonymizeBundle\Tests\Unit\Command\Fixtures\LoggedTestCommand;
use Nowo\AnonymizeBundle\Tests\Unit\Command\Fixtures\RecordingLogger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * REQ-OBS-001: destructive commands log start, success and failure without sensitive data.
 */
final class AbstractCommandRunLoggedTest extends TestCase
{
    public function testSuccessLogsStartAndFinish(): void
    {
        $logger = $this->logger();
        $exit   = $this->command()->runPublic($logger, 'anonymize', ['dry_run' => true], static fn (): int => AbstractCommand::SUCCESS);

        self::assertSame(AbstractCommand::SUCCESS, $exit);
        self::assertSame([['info', 'Anonymize command started.'], ['info', 'Anonymize command finished.']], $this->levels($logger));
        self::assertSame(['bundle' => 'anonymize', 'action' => 'anonymize', 'command' => 'test:logged', 'dry_run' => true], $logger->records[0][2]);
    }

    public function testNonZeroExitLogsAWarningWithTheExitCode(): void
    {
        $logger = $this->logger();
        $exit   = $this->command()->runPublic($logger, 'export_database', [], static fn (): int => AbstractCommand::FAILURE);

        self::assertSame(AbstractCommand::FAILURE, $exit);
        self::assertSame('warning', $logger->records[1][0]);
        self::assertSame(AbstractCommand::FAILURE, $logger->records[1][2]['exit_code']);
    }

    public function testExceptionsAreLoggedAndRethrown(): void
    {
        $logger = $this->logger();

        try {
            $this->command()->runPublic($logger, 'anonymize', [], static fn (): int => throw new RuntimeException('secret dsn mysql://root:pw@db'));
            self::fail('The exception must be rethrown.');
        } catch (RuntimeException) {
        }

        self::assertSame('error', $logger->records[1][0]);
        self::assertSame(RuntimeException::class, $logger->records[1][2]['exception']);
        self::assertStringNotContainsString('pw@db', (string) json_encode($logger->records));
    }

    public function testWithoutLoggerTheBodyStillRuns(): void
    {
        self::assertSame(AbstractCommand::SUCCESS, $this->command()->runPublic(null, 'anonymize', [], static fn (): int => AbstractCommand::SUCCESS));
    }

    private function command(): LoggedTestCommand
    {
        return new LoggedTestCommand();
    }

    private function logger(): RecordingLogger
    {
        return new RecordingLogger();
    }

    /** @return list<array{0: mixed, 1: string}> */
    private function levels(RecordingLogger $logger): array
    {
        return array_map(static fn (array $r): array => [$r[0], $r[1]], $logger->records);
    }
}
