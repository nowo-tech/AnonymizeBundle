<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\EventListener;

use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Clears {@see AnonymizeService}'s faker cache at the end of the OUTERMOST console command (REQ-RUNTIME-002 rule 1).
 *
 * `kernel.reset` alone is not enough: a long-lived process that keeps one kernel without the services resetter
 * (FrankenPHP worker mode with kernel reset false, a scheduler or a test harness running commands in-process) would
 * otherwise keep the faker instances of one run for the next one. A command run in-process from another command
 * (`Application::doRun()`) fires `console.command` / `console.terminate` too; the depth counter makes only the
 * outermost terminate clear, so a nested command never drops the fakers of the run that started it.
 *
 * @internal
 */
final class AnonymizeConsoleBoundaryListener implements EventSubscriberInterface
{
    /** Nesting depth of the console commands in flight (no request data). */
    private int $depth = 0;

    public function __construct(private readonly AnonymizeService $anonymizeService)
    {
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND   => ['onCommand', 4096],
            ConsoleEvents::TERMINATE => ['onTerminate', -4096],
        ];
    }

    public function onCommand(): void
    {
        // @igor-ignore - Console nesting depth, set and unset at the command boundaries (no request data).
        ++$this->depth;
    }

    public function onTerminate(): void
    {
        if ($this->depth > 0) {
            // @igor-ignore - Console nesting depth, set and unset at the command boundaries (no request data).
            --$this->depth;
        }

        if ($this->depth === 0) {
            $this->anonymizeService->reset();
        }
    }
}
