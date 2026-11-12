<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\EventListener;

use Nowo\AnonymizeBundle\EventListener\AnonymizeConsoleBoundaryListener;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Throwable;

use function count;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * 1.6.1 / 1.6.2 (REQ-RUNTIME-002 rule 1; audits NM, NL2-v, NL2-v2): the faker cache is cleared at the start and at the
 * end of the outermost console run only, whatever other console listeners throw or stop. Runs go through a real
 * console Application and a real event dispatcher.
 */
final class AnonymizeConsoleBoundaryListenerTest extends TestCase
{
    private AnonymizeService $service;

    private EventDispatcher $dispatcher;

    private Application $application;

    private AnonymizeConsoleBoundaryListener $listener;

    protected function setUp(): void
    {
        $this->service    = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher());
        $this->listener   = new AnonymizeConsoleBoundaryListener($this->service);
        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->listener);
        $this->application = $this->newApplication();
    }

    public function testSubscribesToTheConsoleBoundaries(): void
    {
        self::assertSame([
            ConsoleEvents::COMMAND   => ['onCommand', PHP_INT_MAX],
            ConsoleEvents::ERROR     => ['onError', PHP_INT_MAX],
            ConsoleEvents::TERMINATE => [['onTerminateStarted', PHP_INT_MAX], ['onTerminate', -4096]],
        ], AnonymizeConsoleBoundaryListener::getSubscribedEvents());
    }

    public function testOutermostRunStartsAndEndsWithAnEmptyCacheAndANestedRunKeepsIt(): void
    {
        $this->warmFaker(); // left over by a run whose terminate never came
        $seen = $this->runOuter('app:inner');

        self::assertSame([1, 1], $seen, 'the outer run starts empty and the nested run keeps its fakers');
        self::assertSame([], $this->cache(), 'the outermost terminate clears the cache');
    }

    /**
     * Audit NM (a): a terminate listener of a nested run throws; the outer run still clears, twice in a row.
     */
    public function testThrowingTerminateListenerOfANestedRun(): void
    {
        $this->dispatcher->addListener(ConsoleEvents::TERMINATE, static function (ConsoleTerminateEvent $event): void {
            if ($event->getCommand()?->getName() === 'app:inner') {
                throw new RuntimeException('terminate listener failed');
            }
        });

        for ($run = 0; $run < 2; ++$run) {
            self::assertSame([1, 1], $this->runOuter('app:inner'));
            self::assertSame([], $this->cache());
        }
    }

    /**
     * Audit NM (c) and (d): an unknown nested command, and a nested console.command stopped above 4096, never clear
     * the outer run mid-run.
     */
    public function testUnknownOrStoppedNestedRunsKeepTheOuterFakers(): void
    {
        self::assertSame([1, 1], $this->runOuter('app:does-not-exist'));
        self::assertSame([], $this->cache());

        $this->dispatcher->addListener(ConsoleEvents::COMMAND, static function (ConsoleCommandEvent $event): void {
            if ($event->getCommand()?->getName() === 'app:inner') {
                $event->stopPropagation();
            }
        }, 10000);
        self::assertSame([1, 1], $this->runOuter('app:inner'));
        self::assertSame([], $this->cache());
    }

    /**
     * Audit NL2-v: a self-recursive command whose inner run fails with a throwing error listener, and an error listener
     * at PHP_INT_MIN that throws, never keep the process inside a run: the next outermost run clears.
     */
    public function testFailedRunsAreHealedAtTheNextCommand(): void
    {
        $throwing = static function (): never {
            throw new RuntimeException('error listener failed');
        };
        $depth     = 0;
        $recursive = (new Command('app:rec'))->setCode(function (InputInterface $input, OutputInterface $output) use (&$depth, $throwing): int {
            if (++$depth > 1) {
                throw new RuntimeException('inner run failed');
            }
            $this->warmFaker();
            $this->dispatcher->addListener(ConsoleEvents::ERROR, $throwing);
            try {
                $this->application->run(new ArrayInput(['command' => 'app:rec']), $output);
            } catch (Throwable) {
            } finally {
                $this->dispatcher->removeListener(ConsoleEvents::ERROR, $throwing);
            }
            self::assertNotSame([], $this->cache(), 'the failed inner run keeps the outer fakers');

            return Command::SUCCESS;
        });
        $failing = (new Command('app:fail'))->setCode(function (): never {
            $this->warmFaker();

            throw new RuntimeException('command failed');
        });
        $this->commands(['app:rec' => $recursive, 'app:fail' => $failing]);

        self::assertSame(Command::SUCCESS, $this->application->run(new ArrayInput(['command' => 'app:rec']), new NullOutput()));
        self::assertSame([], $this->cache(), 'the outer run clears');

        $this->dispatcher->addListener(ConsoleEvents::ERROR, $throwing, PHP_INT_MIN);
        try {
            $this->application->run(new ArrayInput(['command' => 'app:fail']), new NullOutput());
            self::fail('the error listener throws');
        } catch (RuntimeException) {
        } finally {
            $this->dispatcher->removeListener(ConsoleEvents::ERROR, $throwing);
        }
        self::assertNotSame([], $this->cache(), 'the failed run never terminated');

        self::assertSame([1, 1], $this->runOuter('app:inner'), 'the next run is the outermost one and starts empty');
        self::assertSame([], $this->cache());
    }

    public function testResetForgetsTheFrames(): void
    {
        $outer = new ConsoleCommandEvent(new Command('app:outer'), new ArrayInput([]), new NullOutput());
        $this->listener->onCommand($outer);
        $this->listener->onCommand(new ConsoleCommandEvent(new Command('app:inner'), new ArrayInput([]), new NullOutput()));
        $this->listener->reset();

        $this->warmFaker();
        $terminate = new ConsoleTerminateEvent(new Command('app:unknown'), new ArrayInput([]), new NullOutput(), 0);
        $this->listener->onTerminateStarted($terminate);
        $this->listener->onTerminate($terminate);
        self::assertSame([], $this->cache(), 'after reset() nothing runs: a terminate without a frame is the outermost run');
    }

    /**
     * Runs app:outer: it warms one faker, runs $nested in-process (a failure is swallowed) and records the cache size at
     * its start and after the nested run.
     *
     * @return list<int>
     */
    private function runOuter(string $nested): array
    {
        $seen  = [];
        $inner = (new Command('app:inner'))->setCode(static fn (): int => Command::SUCCESS);
        $outer = (new Command('app:outer'))->setCode(function (InputInterface $input, OutputInterface $output) use ($nested, &$seen): int {
            self::assertSame([], $this->cache(), 'the outermost run starts with an empty cache');
            $this->warmFaker();
            $seen[] = count($this->cache());
            try {
                $this->application->doRun(new ArrayInput(['command' => $nested]), $output);
            } catch (Throwable) {
                // The nested run failed (throwing listener, unknown command); the outer run goes on.
            }
            $seen[] = count($this->cache());

            return Command::SUCCESS;
        });
        $this->commands(['app:outer' => $outer, 'app:inner' => $inner]);

        self::assertSame(Command::SUCCESS, $this->application->run(new ArrayInput(['command' => 'app:outer']), new NullOutput()));

        return $seen;
    }

    /**
     * @param array<string, Command> $commands
     */
    private function commands(array $commands): void
    {
        // A fresh Application: it caches the commands it already found.
        $this->application = $this->newApplication();
        $factories         = [];
        foreach ($commands as $name => $command) {
            $factories[$name] = static fn (): Command => $command;
        }
        $this->application->setCommandLoader(new FactoryCommandLoader($factories));
    }

    private function newApplication(): Application
    {
        $application = new Application('anonymize-test');
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        $application->setDispatcher($this->dispatcher);

        return $application;
    }

    private function warmFaker(): void
    {
        (new ReflectionMethod($this->service, 'getFaker'))->invoke($this->service, 'email', null);
        self::assertNotSame([], $this->cache());
    }

    /**
     * @return array<string, mixed>
     */
    private function cache(): array
    {
        $value = (new ReflectionProperty($this->service, 'fakerCache'))->getValue($this->service);
        self::assertIsArray($value);

        return $value;
    }
}
