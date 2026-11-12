<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Metadata;

use InvalidArgumentException;
use Nowo\AnonymizeBundle\Metadata\AnonymizeTarget;
use PHPUnit\Framework\TestCase;

/**
 * 1.6: the ORM-free target of the row SPI and of the events.
 */
final class AnonymizeTargetTest extends TestCase
{
    public function testAccessors(): void
    {
        $target = new AnonymizeTarget('App\Domain\Child', 'party', ['id'], ['id' => 'id', 'email' => 'email_address', 'address.street' => 'address_street'], 'kind', 'child');

        self::assertSame('App\Domain\Child', $target->getEntityClass());
        self::assertSame('party', $target->getTableName());
        self::assertSame(['id'], $target->getIdentifierColumns());
        self::assertSame(['id' => 'id', 'email' => 'email_address', 'address.street' => 'address_street'], $target->getColumns());
        self::assertTrue($target->hasField('address.street'));
        self::assertFalse($target->hasField('address'));
        self::assertSame('email_address', $target->getColumnName('email'));
        self::assertSame('kind', $target->getDiscriminatorColumn());
        self::assertSame('child', $target->getDiscriminatorValue());
    }

    public function testDefaults(): void
    {
        $target = new AnonymizeTarget('App\Domain\Log', 'log');

        self::assertSame([], $target->getIdentifierColumns());
        self::assertSame([], $target->getColumns());
        self::assertNull($target->getDiscriminatorColumn());
        self::assertNull($target->getDiscriminatorValue());
    }

    public function testUnknownFieldFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "nickname" is not mapped on App\Domain\Log (table "log").');

        (new AnonymizeTarget('App\Domain\Log', 'log'))->getColumnName('nickname');
    }
}
