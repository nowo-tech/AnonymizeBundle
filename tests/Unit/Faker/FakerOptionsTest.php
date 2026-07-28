<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Faker;

use Nowo\AnonymizeBundle\Faker\FakerOptions;
use PHPUnit\Framework\TestCase;

/**
 * Test case for FakerOptions (REQ-PHP-001).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class FakerOptionsTest extends TestCase
{
    public function testFromArrayCreatesInstance(): void
    {
        $opts = FakerOptions::fromArray(['key' => 'value', 'count' => 3]);

        self::assertSame('value', $opts->get('key'));
        self::assertSame(3, $opts->get('count'));
    }

    public function testFromArrayWithEmptyArray(): void
    {
        $opts = FakerOptions::fromArray([]);

        self::assertSame([], $opts->all());
    }

    public function testNormalizeWithArray(): void
    {
        $opts = FakerOptions::normalize(['foo' => 'bar']);

        self::assertSame('bar', $opts->get('foo'));
    }

    public function testNormalizeWithFakerOptionsReturnsSameInstance(): void
    {
        $original   = FakerOptions::fromArray(['x' => 1]);
        $normalized = FakerOptions::normalize($original);

        self::assertSame($original, $normalized);
    }

    public function testGetReturnsDefaultWhenKeyMissing(): void
    {
        $opts = FakerOptions::fromArray([]);

        self::assertNull($opts->get('missing'));
        self::assertSame('default', $opts->get('missing', 'default'));
        self::assertSame(42, $opts->get('missing', 42));
    }

    public function testGetReturnsValueWhenKeyPresent(): void
    {
        $opts = FakerOptions::fromArray(['locale' => 'es_ES', 'min' => 0]);

        self::assertSame('es_ES', $opts->get('locale'));
        self::assertSame(0, $opts->get('min', 99));
    }

    public function testHasReturnsTrueWhenKeyExists(): void
    {
        $opts = FakerOptions::fromArray(['original_value' => null]);

        self::assertTrue($opts->has('original_value'));
    }

    public function testHasReturnsFalseWhenKeyMissing(): void
    {
        $opts = FakerOptions::fromArray([]);

        self::assertFalse($opts->has('original_value'));
    }

    public function testAllReturnsAllValues(): void
    {
        $values = ['a' => 1, 'b' => 'two', 'c' => true];
        $opts   = FakerOptions::fromArray($values);

        self::assertSame($values, $opts->all());
    }

    public function testWithReturnNewInstanceWithAddedKey(): void
    {
        $original = FakerOptions::fromArray(['a' => 1]);
        $modified = $original->with('b', 2);

        self::assertNotSame($original, $modified);
        self::assertFalse($original->has('b'));
        self::assertSame(2, $modified->get('b'));
        self::assertSame(1, $modified->get('a'));
    }

    public function testWithOverwritesExistingKey(): void
    {
        $opts    = FakerOptions::fromArray(['key' => 'old']);
        $updated = $opts->with('key', 'new');

        self::assertSame('old', $opts->get('key'));
        self::assertSame('new', $updated->get('key'));
    }

    public function testMergeAddsNewKeys(): void
    {
        $opts   = FakerOptions::fromArray(['a' => 1]);
        $merged = $opts->merge(['b' => 2, 'c' => 3]);

        self::assertNotSame($opts, $merged);
        self::assertSame(1, $merged->get('a'));
        self::assertSame(2, $merged->get('b'));
        self::assertSame(3, $merged->get('c'));
        self::assertFalse($opts->has('b'));
    }

    public function testMergeOverwritesExistingKeys(): void
    {
        $opts   = FakerOptions::fromArray(['key' => 'original', 'keep' => 'yes']);
        $merged = $opts->merge(['key' => 'overwritten']);

        self::assertSame('overwritten', $merged->get('key'));
        self::assertSame('yes', $merged->get('keep'));
    }

    public function testIsImmutable(): void
    {
        $opts = FakerOptions::fromArray(['x' => 1]);

        $opts->with('y', 2);
        $opts->merge(['z' => 3]);

        self::assertFalse($opts->has('y'));
        self::assertFalse($opts->has('z'));
    }

    public function testNormalizeAllPreservesArrayValues(): void
    {
        $array = ['original_value' => 'test@example.com', 'record' => ['id' => 5]];
        $opts  = FakerOptions::normalize($array);

        self::assertSame('test@example.com', $opts->get('original_value'));
        self::assertSame(['id' => 5], $opts->get('record'));
    }
}
