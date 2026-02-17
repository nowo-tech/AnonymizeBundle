<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Nowo\AnonymizeBundle\Command\AbstractCommand;
use PHPUnit\Framework\TestCase;

/**
 * Test case for AbstractCommand.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AbstractCommandTest extends TestCase
{
    /**
     * Test that quoteIdentifier delegates to DbalHelper.
     */
    public function testQuoteIdentifier(): void
    {
        // Create a concrete implementation of AbstractCommand for testing
        $command = new class extends AbstractCommand {
            public function testQuoteIdentifier(Connection $connection, string $identifier): string
            {
                return $this->quoteIdentifier($connection, $identifier);
            }
        };

        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('quoteSingleIdentifier')->with('test_table')->willReturn('`test_table`');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $result = $command->testQuoteIdentifier($connection, 'test_table');
        $this->assertEquals('`test_table`', $result);
    }

    /**
     * Test that quoteIdentifier handles different identifiers.
     */
    public function testQuoteIdentifierWithDifferentIdentifiers(): void
    {
        $command = new class extends AbstractCommand {
            public function testQuoteIdentifier(Connection $connection, string $identifier): string
            {
                return $this->quoteIdentifier($connection, $identifier);
            }
        };

        $connection = $this->createMock(Connection::class);
        $platform   = $this->createMock(AbstractPlatform::class);

        $connection->method('getDatabasePlatform')
            ->willReturn($platform);

        $platform->method('quoteSingleIdentifier')
            ->willReturnCallback(static function ($identifier) {
                return '`' . $identifier . '`';
            });

        // Test that the method is called correctly
        $result1 = $command->testQuoteIdentifier($connection, 'users');
        $this->assertIsString($result1);

        $result2 = $command->testQuoteIdentifier($connection, 'email');
        $this->assertIsString($result2);

        $result3 = $command->testQuoteIdentifier($connection, 'user_id');
        $this->assertIsString($result3);
    }
}
