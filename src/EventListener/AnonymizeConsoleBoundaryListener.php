<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\EventListener;

use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Worker\UnitOfWorkFrames;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ResetInterface;

use const PHP_INT_MAX;

/**
 * Clears {@see AnonymizeService}'s faker cache at the start and at the end of the OUTERMOST console command
 * (REQ-RUNTIME-002 rule 1).
 *
 * `kernel.reset` alone is not enough: a long-lived process that keeps one kernel without the services resetter
 * (FrankenPHP worker mode with kernel reset false, a scheduler or a test harness running commands in-process) would
 * otherwise keep the faker instances of one run for the next one. A command run in-process from another command
 * (`Application::doRun()`) fires `console.command` / `console.terminate` too; only the outermost run clears, so a
 * nested command never drops the fakers of the run that started it.
 *
 * Which run is outermost is tracked by {@see UnitOfWorkFrames} (1.6.2, audits NM / NL2-v / NL2-v2), fed at priority
 * `PHP_INT_MAX` so another listener that throws or stops propagation cannot unbalance it: one frame per console run
 * (Command + input), a frame left failing by a throwing `console.error` listener healed at the next command. The
 * clear at the outermost START (right after the frame push) also heals a run whose terminate never came (audit NL L1);
 * the end-of-run clear keeps its priority (-4096).
 *
 * @internal
 */
final readonly class AnonymizeConsoleBoundaryListener implements EventSubscriberInterface, ResetInterface
{
    /** Console runs in flight (deliberately not tagged `kernel.reset`; the service is not autoconfigured). */
    private UnitOfWorkFrames $frames;

    public function __construct(private AnonymizeService $anonymizeService)
    {
        $this->frames = new UnitOfWorkFrames();
    }

    /**
     * @return array<string, array{0: string, 1: int}|list<array{0: string, 1: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // Bookkeeping first (PHP_INT_MAX), the end-of-run clear late: a listener in between cannot skip it.
            ConsoleEvents::COMMAND   => ['onCommand', PHP_INT_MAX],
            ConsoleEvents::ERROR     => ['onError', PHP_INT_MAX],
            ConsoleEvents::TERMINATE => [['onTerminateStarted', PHP_INT_MAX], ['onTerminate', -4096]],
        ];
    }

    /**
     * A console run starts: pushes its frame; the outermost run starts with an empty faker cache.
     */
    public function onCommand(ConsoleCommandEvent $event): void
    {
        if ($this->frames->commandStarted($event)) {
            $this->anonymizeService->reset();
        }
    }

    /**
     * A console run failed (bookkeeping: its frame is marked failing until its own terminate).
     */
    public function onError(ConsoleErrorEvent $event): void
    {
        $this->frames->commandFailed($event);
    }

    /**
     * A console run ends (bookkeeping: its frame is removed, the outermost verdict kept for the late clear).
     */
    public function onTerminateStarted(ConsoleTerminateEvent $event): void
    {
        $this->frames->commandTerminated($event);
    }

    /**
     * The outermost run ends: the faker cache is cleared.
     */
    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        if ($this->frames->isOutermost($event)) {
            $this->anonymizeService->reset();
        }
    }

    /**
     * Forgets the console bookkeeping (not the faker cache). Not tagged `kernel.reset` and not relied on in worker mode.
     */
    public function reset(): void
    {
        $this->frames->reset();
    }
}
