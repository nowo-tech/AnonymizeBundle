<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Worker;

use Nowo\AnonymizeBundle\Worker\UnitOfWorkFrames;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class UnitOfWorkFramesTest extends TestCase
{
    public function testOutermostCommandAndNestedRuns(): void
    {
        $frames = new UnitOfWorkFrames();
        $outer  = $this->start($frames, new Command('app:outer'));
        self::assertTrue($outer[1]);
        $inner = $this->start($frames, new Command('app:inner'));
        self::assertFalse($inner[1]);
        self::assertSame(2, $frames->runningCommands());

        self::assertFalse($frames->commandTerminated($this->terminate($inner[0])));
        self::assertTrue($frames->commandTerminated($terminate = $this->terminate($outer[0])));
        self::assertTrue($frames->isOutermost($terminate));
        self::assertTrue($frames->isIdle());
    }

    public function testSelfRecursiveRunsAreTwoFrames(): void
    {
        $frames  = new UnitOfWorkFrames();
        $command = new Command('app:rec');
        $outer   = $this->start($frames, $command);
        $inner   = $this->start($frames, $command);

        self::assertFalse($frames->commandTerminated($this->terminate($inner[0])));
        self::assertSame(1, $frames->runningCommands(), 'Only the inner run (its own input) is removed.');
        self::assertTrue($frames->commandTerminated($this->terminate($outer[0])));
    }

    public function testTerminateOfAnOuterRunRemovesTheNestedRunsThatNeverTerminated(): void
    {
        $frames = new UnitOfWorkFrames();
        $outer  = $this->start($frames, new Command('app:outer'));
        $this->start($frames, new Command('app:inner'));

        self::assertTrue($frames->commandTerminated($this->terminate($outer[0])));
        self::assertSame(0, $frames->runningCommands());
    }

    public function testUnknownTerminateNeverRemovesAnotherRunsFrame(): void
    {
        $frames  = new UnitOfWorkFrames();
        $outer   = $this->start($frames, new Command('app:outer'));
        $unknown = $this->terminate(new ConsoleCommandEvent(new Command('app:unknown'), new ArrayInput([]), new NullOutput()));

        self::assertFalse($frames->commandTerminated($unknown));
        self::assertSame(1, $frames->runningCommands());
        self::assertTrue($frames->commandTerminated($this->terminate($outer[0])));
        // Nothing runs any more: an unknown terminate is the outermost unit.
        self::assertTrue($frames->commandTerminated($unknown));
    }

    public function testErrorMarksTheFrameUntilItsOwnTerminate(): void
    {
        $frames = new UnitOfWorkFrames();
        $outer  = $this->start($frames, new Command('app:outer'));
        $error  = new ConsoleErrorEvent($outer[0]->getInput(), $outer[0]->getOutput(), new RuntimeException('failed'), $outer[0]->getCommand());

        self::assertTrue($frames->commandFailed($error));
        self::assertTrue($frames->isOutermost($error));
        self::assertTrue($frames->commandTerminated($this->terminate($outer[0])));
        self::assertTrue($frames->isIdle());
    }

    public function testFailedFrameWithoutTerminateIsDroppedAtTheNextUnit(): void
    {
        foreach (['request', 'message', 'command', 'worker'] as $next) {
            $frames = new UnitOfWorkFrames();
            $outer  = $this->start($frames, new Command('app:outer'));
            $frames->commandFailed(new ConsoleErrorEvent($outer[0]->getInput(), $outer[0]->getOutput(), new RuntimeException('failed'), $outer[0]->getCommand()));

            match ($next) {
                'request' => $frames->requestStarted(),
                'message' => $frames->messageReceived(),
                'command' => self::assertTrue($this->start($frames, new Command('app:next'))[1], 'The next command is the outermost unit.'),
                'worker'  => $frames->workerStopped(),
            };

            self::assertSame($next === 'command' ? 1 : 0, $frames->runningCommands(), $next);
        }
    }

    public function testErrorWithoutCommandIsOutermostOnlyWhenIdle(): void
    {
        $frames = new UnitOfWorkFrames();
        $error  = new ConsoleErrorEvent(new ArrayInput([]), new NullOutput(), new RuntimeException('not found'));
        self::assertTrue($frames->commandFailed($error));

        $this->start($frames, new Command('app:outer'));
        self::assertFalse($frames->commandFailed(new ConsoleErrorEvent(new ArrayInput([]), new NullOutput(), new RuntimeException('not found'))));
        self::assertSame(1, $frames->runningCommands(), 'An unknown command never marks the running frame.');
    }

    public function testFramesSaveAndRestoreTheOuterMessageAndRequestState(): void
    {
        $frames = new UnitOfWorkFrames();
        $frames->messageReceived();
        $inMessage = $this->start($frames, new Command('app:in-message'));
        self::assertFalse($inMessage[1]);
        $frames->messageFinished();
        self::assertTrue($frames->commandTerminated($this->terminate($inMessage[0])) === false);
        self::assertTrue($frames->isMessageInFlight(), 'The frame restores the outer message state.');
        $frames->messageFinished();

        $command = $this->start($frames, new Command('app:consume'));
        $frames->messageReceived(); // a received listener threw: no handled / failed / stopped follows
        $frames->requestStarted();  // a request handled in-process never finished
        self::assertTrue($frames->commandTerminated($this->terminate($command[0])));
        self::assertFalse($frames->isMessageInFlight());
        self::assertFalse($frames->isRequestInFlight());
        self::assertTrue($frames->isIdle());
    }

    /**
     * Audit NL2-v2: a message handler runs a command that fails and whose console.error listener throws (no
     * terminate); the message fails, the worker stops: the healed frame never leaves the message flag stuck.
     */
    public function testFailedCommandInsideAMessageNeverLeavesTheMessageInFlight(): void
    {
        foreach ([true, false] as $messageFailedEventDispatched) {
            $frames = new UnitOfWorkFrames();
            $frames->messageReceived();
            $command = $this->start($frames, new Command('app:in-handler'));
            $frames->commandFailed(new ConsoleErrorEvent($command[0]->getInput(), $command[0]->getOutput(), new RuntimeException('failed'), $command[0]->getCommand()));

            if ($messageFailedEventDispatched) {
                $frames->messageFinished();
                self::assertFalse($frames->isMessageInFlight(), 'The failed message is no longer in flight.');
            }
            $frames->workerStopped();

            self::assertTrue($frames->isIdle(), 'Nothing runs after the worker stopped.');
            $frames->requestStarted();
            self::assertSame(0, $frames->runningCommands());
            $frames->requestFinished();
            self::assertTrue($frames->isIdle());
        }
    }

    public function testFailedCommandInsideARequestIsHealedWhenTheRequestFinishes(): void
    {
        $frames = new UnitOfWorkFrames();
        $frames->requestStarted();
        $command = $this->start($frames, new Command('app:in-controller'));
        $frames->commandFailed(new ConsoleErrorEvent($command[0]->getInput(), $command[0]->getOutput(), new RuntimeException('failed'), $command[0]->getCommand()));
        $frames->requestFinished();

        self::assertTrue($frames->isIdle());
    }

    public function testNestedMainRequestsKeepTheOuterOneInFlight(): void
    {
        $frames = new UnitOfWorkFrames();
        $frames->requestStarted();
        $frames->requestStarted();
        $frames->requestFinished();
        self::assertTrue($frames->isRequestInFlight());
        $frames->requestFinished();
        $frames->requestFinished();
        self::assertFalse($frames->isRequestInFlight());
    }

    public function testCommandWithoutCommandObjectAndUnseenEvents(): void
    {
        $frames = new UnitOfWorkFrames();
        $event  = new ConsoleCommandEvent(null, new ArrayInput([]), new NullOutput());
        self::assertTrue($frames->commandStarted($event));
        self::assertFalse($frames->isOutermost(new stdClass()), 'An unseen event is judged on the current state.');

        $frames->reset();
        self::assertTrue($frames->isIdle());
        self::assertTrue($frames->isOutermost(new stdClass()));
    }

    /**
     * @return array{0: ConsoleCommandEvent, 1: bool}
     */
    private function start(UnitOfWorkFrames $frames, Command $command): array
    {
        $event = new ConsoleCommandEvent($command, new ArrayInput([]), new NullOutput());

        return [$event, $frames->commandStarted($event)];
    }

    private function terminate(ConsoleCommandEvent $started): ConsoleTerminateEvent
    {
        return new ConsoleTerminateEvent($started->getCommand() ?? new Command('app:none'), $started->getInput(), $started->getOutput(), 0);
    }
}
