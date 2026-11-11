<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Helper;

use Nowo\AnonymizeBundle\Helper\RowKey;
use PHPUnit\Framework\TestCase;

/**
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class RowKeyTest extends TestCase
{
    public function testExactKeyWinsEvenWithANullValue(): void
    {
        self::assertSame('fullName', RowKey::resolve(['fullName' => null, 'fullname' => 'x'], 'fullName'));
    }

    public function testTheOneKeyDifferingOnlyInLetterCaseIsUsed(): void
    {
        self::assertSame('fullname', RowKey::resolve(['id' => 1, 'fullname' => 'x'], 'fullName'));
        self::assertSame('FULLNAME', RowKey::resolve(['FULLNAME' => 'x'], 'fullName'));
    }

    public function testMissingOrAmbiguousKeysGiveNull(): void
    {
        self::assertNull(RowKey::resolve(['id' => 1], 'fullName'));
        self::assertNull(RowKey::resolve(['fullname' => 'a', 'FULLNAME' => 'b'], 'fullName'));
    }
}
