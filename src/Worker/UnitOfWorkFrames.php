<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Worker;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use WeakMap;

use function array_splice;
use function count;
use function max;

/**
 * Tracks which unit of work runs in this process, so a boundary listener acts only at the OUTERMOST request, Messenger
 * message or console command (REQ-RUNTIME-002; audits NM and NL2-v, same design as PageBuilderKitBundle
 * `UnitOfWorkBoundaries`). It is not a service: the owning listener creates it and feeds it from its own listeners
 * registered at priority `PHP_INT_MAX`, before any other listener can throw or stop propagation.
 *
 * - Console: every run is a FRAME on a stack, keyed by the Command object AND the input object (a command that runs
 *   itself in-process has a different input per run). A frame records whether its run was the outermost unit (stack
 *   empty, no message and no main request in flight when it started) and saves the outer message / request state,
 *   restored when the frame is removed. `console.terminate` removes ITS frame and the frames above it (nested runs
 *   that never terminated); a terminate without a frame never removes another run's frame.
 * - `console.error` marks the frame as failing; the mark stays until the frame's own terminate removes it. When an
 *   error listener throws, Symfony never dispatches that terminate: the still-marked frame is dropped (and its saved
 *   state restored) at the next main request, message, command or worker stop, so the process never stays "inside a
 *   command" for good.
 * - HTTP: a main-request depth (main `kernel.request` → main `kernel.finish_request`), so a main request handled
 *   in-process inside another one does not end the outer one early.
 * - Messenger: a message in flight (received → handled / failed / skipped / worker stopped). `WorkerMessageSkipEvent`
 *   is only dispatched by `messenger:failed:retry`.
 *
 * The verdict of each console event ("did it start / end the outermost unit?") is kept per event object (`WeakMap`),
 * so a late listener reads the value computed when the event started, whatever ran in between.
 *
 * Ordering rule (audit NL2-v2): every handler first drops the frames left failing (restoring the state they saved),
 * THEN writes its own flag, so a healed frame never overwrites the handler's own state.
 *
 * Process-lifetime state bounded by the nesting depth, describing the units still running; not tagged `kernel.reset`.
 *
 * @internal
 */
final class UnitOfWorkFrames
{
    /** @var list<array{command: object, input: object, outermost: bool, failing: bool, message: bool, requests: int}> running console runs, outermost first */
    private array $frames = [];

    private bool $messageInFlight = false;

    private int $requestDepth = 0;

    /** @var WeakMap<object, bool> outermost verdict per console event */
    private WeakMap $verdicts;

    public function __construct()
    {
        $this->verdicts = new WeakMap();
    }

    /** Main `kernel.request`: heals a failed run, then one more main request is in flight. */
    public function requestStarted(): void
    {
        $this->dropFailedFrames();
        // @igor-ignore - Main-request nesting depth, restored by the console frames (no request data).
        ++$this->requestDepth;
    }

    /** Main `kernel.finish_request`: heals a failed run first, then the main request ends. */
    public function requestFinished(): void
    {
        $this->dropFailedFrames();
        // @igor-ignore - Main-request nesting depth, restored by the console frames (no request data).
        $this->requestDepth = max(0, $this->requestDepth - 1);
    }

    /** Messenger message received: heals a failed run, then a message is in flight. */
    public function messageReceived(): void
    {
        $this->dropFailedFrames();
        // @igor-ignore - Unit-of-work flag, set and unset at the Messenger boundaries.
        $this->messageInFlight = true;
    }

    /** Messenger message handled, failed or skipped: heals a failed run first, then no message is in flight. */
    public function messageFinished(): void
    {
        $this->dropFailedFrames();
        // @igor-ignore - Unit-of-work flag, set and unset at the Messenger boundaries.
        $this->messageInFlight = false;
    }

    /** The worker stopped (possibly after a message without handled / failed): no message in flight any more. */
    public function workerStopped(): void
    {
        // Heal first (it restores the state saved by the dropped frame), then write this handler's own flag.
        $this->dropFailedFrames();
        // @igor-ignore - Unit-of-work flag, set and unset at the Messenger boundaries.
        $this->messageInFlight = false;
    }

    /**
     * A console run starts: pushes its frame. Returns whether it is the outermost unit.
     */
    public function commandStarted(ConsoleCommandEvent $event): bool
    {
        $this->dropFailedFrames();
        $outermost = $this->isIdle();
        // @igor-ignore - Per-event verdict; the WeakMap entry dies with the event.
        $this->verdicts[$event] = $outermost;
        // @igor-ignore - Stack of the running console runs (no request data), popped by their own terminate.
        $this->frames[] = [
            'command'   => $event->getCommand() ?? $event,
            'input'     => $event->getInput(),
            'outermost' => $outermost,
            'failing'   => false,
            'message'   => $this->messageInFlight,
            'requests'  => $this->requestDepth,
        ];

        return $outermost;
    }

    /**
     * A console run failed (or no command was found: no frame). Marks its frame as failing until its own terminate.
     * Returns whether the failing run is the outermost unit; without a frame, only when nothing else runs.
     */
    public function commandFailed(ConsoleErrorEvent $event): bool
    {
        $index = $this->indexOf($event->getCommand(), $event->getInput());
        if ($index === null) {
            $outermost = $this->isIdle();
            // @igor-ignore - Per-event verdict; the WeakMap entry dies with the event.
            $this->verdicts[$event] = $outermost;

            return $outermost;
        }

        $outermost = $this->frames[$index]['outermost'];
        // @igor-ignore - Failure marker of a running console run, removed with its frame.
        $this->frames[$index]['failing'] = true;
        // @igor-ignore - Per-event verdict; the WeakMap entry dies with the event.
        $this->verdicts[$event] = $outermost;

        return $outermost;
    }

    /**
     * A console run ends: removes its frame and the nested frames above it, restoring the state saved by its frame.
     * Returns whether it was the outermost unit; without a frame, only when nothing else runs.
     */
    public function commandTerminated(ConsoleTerminateEvent $event): bool
    {
        $index = $this->indexOf($event->getCommand(), $event->getInput());
        if ($index === null) {
            $this->dropFailedFrames();
            $outermost = $this->isIdle();
            // @igor-ignore - Per-event verdict; the WeakMap entry dies with the event.
            $this->verdicts[$event] = $outermost;

            return $outermost;
        }

        $outermost = $this->frames[$index]['outermost'];
        // @igor-ignore - Per-event verdict; the WeakMap entry dies with the event.
        $this->verdicts[$event] = $outermost;
        $this->removeFrom($index);

        return $outermost;
    }

    /**
     * Whether a console event (seen by this tracker) started or ended the outermost unit. An event the tracker did not
     * see is judged on the current state.
     */
    public function isOutermost(object $consoleEvent): bool
    {
        return $this->verdicts[$consoleEvent] ?? $this->isIdle();
    }

    /** Nothing runs: no console run, no message and no main request in flight. */
    public function isIdle(): bool
    {
        return $this->frames === [] && !$this->messageInFlight && $this->requestDepth === 0;
    }

    public function isMessageInFlight(): bool
    {
        return $this->messageInFlight;
    }

    public function isRequestInFlight(): bool
    {
        return $this->requestDepth > 0;
    }

    public function runningCommands(): int
    {
        return count($this->frames);
    }

    /** Forgets everything (tests and explicit resets only; never wired to `kernel.reset`). */
    public function reset(): void
    {
        $this->frames          = [];
        $this->messageInFlight = false;
        $this->requestDepth    = 0;
        $this->verdicts        = new WeakMap();
    }

    private function indexOf(?object $command, object $input): ?int
    {
        if ($command === null) {
            return null;
        }

        for ($i = count($this->frames) - 1; $i >= 0; --$i) {
            if ($this->frames[$i]['command'] === $command && $this->frames[$i]['input'] === $input) {
                return $i;
            }
        }

        return null;
    }

    /** Removes the frame at $index and every frame above it, restoring the outer state saved by that frame. */
    private function removeFrom(int $index): void
    {
        // @igor-ignore - Unit-of-work state restored from the removed frame.
        $this->messageInFlight = $this->frames[$index]['message'];
        // @igor-ignore - Unit-of-work state restored from the removed frame.
        $this->requestDepth = $this->frames[$index]['requests'];
        // @igor-ignore - Stack of the running console runs (no request data).
        array_splice($this->frames, $index);
    }

    /**
     * Drops the first frame still marked failing (its error listener threw, so Symfony never dispatched its
     * terminate), with the frames above it.
     */
    private function dropFailedFrames(): void
    {
        foreach ($this->frames as $i => $frame) {
            if ($frame['failing']) {
                $this->removeFrom($i);

                return;
            }
        }
    }
}
