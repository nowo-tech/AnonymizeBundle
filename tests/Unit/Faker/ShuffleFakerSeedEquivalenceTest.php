<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Faker;

use Nowo\AnonymizeBundle\Faker\ShuffleFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function current;
use function mt_rand;
use function mt_srand;
use function range;
use function shuffle;

use const PHP_INT_MAX;

/**
 * 1.6.1: a seeded {@see ShuffleFaker} shuffles on a local `Random\Randomizer(new Mt19937($seed))` instead of
 * `mt_srand($seed)` + `shuffle()` + `mt_srand()`. For the same seed it must return exactly what 1.6.0 returned, and it
 * must no longer touch the process-wide `mt_rand()` state (REQ-RUNTIME-002).
 */
final class ShuffleFakerSeedEquivalenceTest extends TestCase
{
    /**
     * @return array<string, array<mixed>>
     */
    private static function pools(): array
    {
        return [
            'letters'     => ['a', 'b', 'c', 'd', 'e'],
            'numbers'     => range(1, 20),
            'string keys' => ['x' => 'X', 'y' => 'Y', 'z' => 'Z', 'w' => 'W'],
            'pair'        => ['left', 'right'],
            'single'      => ['only'],
        ];
    }

    /**
     * @return list<int|string>
     */
    private static function seeds(): array
    {
        return [0, 1, 7, 42, 12345, -7, 4294967301, PHP_INT_MAX, '99'];
    }

    /**
     * @return iterable<string, array{int|string, array<mixed>, mixed}>
     */
    public static function seededCases(): iterable
    {
        foreach (self::pools() as $poolName => $pool) {
            foreach (self::seeds() as $seed) {
                yield $poolName . ', seed ' . $seed => [$seed, $pool, null];
                if ($poolName !== 'single') {
                    yield $poolName . ', seed ' . $seed . ', excluding the first value' => [$seed, $pool, current($pool)];
                }
            }
        }
    }

    /**
     * @param array<mixed> $values
     */
    #[DataProvider('seededCases')]
    public function testSeededOutputEqualsTheFormerMtSrandShuffle(int|string $seed, array $values, mixed $exclude): void
    {
        $expected = $this->legacyGenerate($values, $seed, $exclude);
        $options  = ['values' => $values, 'seed' => $seed];
        if ($exclude !== null) {
            $options['exclude'] = $exclude;
        }

        self::assertSame($expected, (new ShuffleFaker())->generate($options));
    }

    /**
     * Outputs recorded from the 1.6.0 algorithm (`mt_srand($seed)` + `shuffle()`) on PHP 8.2.35: pinned so a change of PHP's Mt19937 or
     * shuffle algorithm is noticed even though the legacy reference above would move with it.
     *
     * @return iterable<string, array{int, list<mixed>, mixed}>
     */
    public static function pinnedOutputs(): iterable
    {
        yield 'letters, seed 0' => [0, ['a', 'b', 'c', 'd', 'e'], 'b'];
        yield 'letters, seed 42' => [42, ['a', 'b', 'c', 'd', 'e'], 'e'];
        yield 'letters, seed 12345' => [12345, ['a', 'b', 'c', 'd', 'e'], 'c'];
        yield 'numbers, seed 1' => [1, range(1, 20), 7];
        yield 'numbers, seed 42' => [42, range(1, 20), 1];
        yield 'numbers, seed -7' => [-7, range(1, 20), 7];
    }

    /**
     * @param list<mixed> $values
     */
    #[DataProvider('pinnedOutputs')]
    public function testSeededOutputMatchesTheRecordedOutputs(int $seed, array $values, mixed $expected): void
    {
        self::assertSame($expected, (new ShuffleFaker())->generate(['values' => $values, 'seed' => $seed]));
    }

    public function testRepeatedCallsWithTheSameSeedReturnTheSameValue(): void
    {
        $faker = new ShuffleFaker();
        $first = $faker->generate(['values' => range(1, 50), 'seed' => 2026]);

        for ($i = 0; $i < 100; ++$i) {
            self::assertSame($first, $faker->generate(['values' => range(1, 50), 'seed' => 2026]));
        }
    }

    public function testSeededCallDoesNotTouchTheGlobalMtRandState(): void
    {
        mt_srand(31337);
        $expected = [mt_rand(), mt_rand(), mt_rand()];

        mt_srand(31337);
        $first = mt_rand();
        (new ShuffleFaker())->generate(['values' => range(1, 10), 'seed' => 42]);
        $rest = [mt_rand(), mt_rand()];

        // 1.6.0 reseeded mt_rand() (mt_srand($seed), then mt_srand()): the caller's sequence was lost.
        self::assertSame($expected, [$first, ...$rest]);
        mt_srand();
    }

    /**
     * The 1.6.0 algorithm, kept here as the reference.
     *
     * @param array<mixed> $values
     */
    private function legacyGenerate(array $values, int|string $seed, mixed $exclude): mixed
    {
        if ($exclude !== null) {
            $values = array_filter($values, static fn ($value): bool => $value !== $exclude);
        }

        mt_srand((int) $seed);
        $shuffled = $values;
        shuffle($shuffled);
        mt_srand();

        return $shuffled[0];
    }
}
