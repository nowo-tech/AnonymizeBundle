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
use Symfony\Component\Console\ConsoleEvents;

/**
 * 1.6.1 (REQ-RUNTIME-002 rule 1): the faker cache is cleared at the outermost `console.terminate` only.
 */
final class AnonymizeConsoleBoundaryListenerTest extends TestCase
{
    private AnonymizeService $service;

    protected function setUp(): void
    {
        $this->service = new AnonymizeService(new FakerFactory('en_US'), new PatternMatcher());
    }

    public function testSubscribesToTheConsoleBoundaries(): void
    {
        self::assertSame([
            ConsoleEvents::COMMAND   => ['onCommand', 4096],
            ConsoleEvents::TERMINATE => ['onTerminate', -4096],
        ], AnonymizeConsoleBoundaryListener::getSubscribedEvents());
    }

    public function testOutermostTerminateClearsTheFakerCache(): void
    {
        $listener = new AnonymizeConsoleBoundaryListener($this->service);

        $listener->onCommand();
        $this->warmFaker();
        $listener->onTerminate();

        self::assertSame([], $this->cache());
    }

    public function testNestedCommandKeepsTheOuterCommandsFakers(): void
    {
        $listener = new AnonymizeConsoleBoundaryListener($this->service);

        $listener->onCommand();
        $this->warmFaker();
        $listener->onCommand();
        $listener->onTerminate();
        self::assertNotSame([], $this->cache(), 'a nested command does not clear the outer run');

        $listener->onTerminate();
        self::assertSame([], $this->cache());
    }

    public function testTerminateWithoutCommandStillClears(): void
    {
        $listener = new AnonymizeConsoleBoundaryListener($this->service);
        $this->warmFaker();

        $listener->onTerminate();
        self::assertSame([], $this->cache());

        // The depth never goes below zero: the next command is outermost again.
        $listener->onCommand();
        $this->warmFaker();
        $listener->onTerminate();
        self::assertSame([], $this->cache());
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
