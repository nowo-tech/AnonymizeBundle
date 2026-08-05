<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Command;

use InvalidArgumentException;
use Nowo\AnonymizeBundle\Command\AnonymizeCommand;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class AnonymizeStatsPathTest extends TestCase
{
    public function testRelativeStatsPathStaysInsideBase(): void
    {
        $base = sys_get_temp_dir() . '/anonymize_stats_' . uniqid('', true);
        mkdir($base, 0o755, true);

        try {
            $resolved = $this->invokeResolve('report.json', $base);
            $this->assertSame(str_replace('\\', '/', (string) realpath($base)) . '/report.json', str_replace('\\', '/', $resolved));
        } finally {
            @rmdir($base);
        }
    }

    public function testTraversalRelativePathIsRejected(): void
    {
        $base = sys_get_temp_dir() . '/anonymize_stats_' . uniqid('', true);
        mkdir($base, 0o755, true);

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->invokeResolve('../escape.json', $base);
        } finally {
            @rmdir($base);
        }
    }

    public function testAbsolutePathOutsideBaseIsRejected(): void
    {
        $base = sys_get_temp_dir() . '/anonymize_stats_' . uniqid('', true);
        mkdir($base, 0o755, true);

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->invokeResolve('/tmp/outside-anonymize-stats.json', $base);
        } finally {
            @rmdir($base);
        }
    }

    private function invokeResolve(string $userPath, string $base): string
    {
        $command = (new ReflectionClass(AnonymizeCommand::class))->newInstanceWithoutConstructor();
        $method  = new ReflectionMethod(AnonymizeCommand::class, 'resolveStatsOutputPath');
        $method->setAccessible(true);

        return $method->invoke($command, $userPath, $base);
    }
}
