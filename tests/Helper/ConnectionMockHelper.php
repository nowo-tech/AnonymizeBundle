<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Helper;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Helper to create Doctrine DBAL Connection mocks compatible with PHP 8.1 and 8.2.
 * On PHP 8.1 with DBAL 2.x, quoteSingleIdentifier may not exist; on DBAL 3.x it may be final
 * and thus not mockable. We add the method via addMethods() only when it does not exist.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class ConnectionMockHelper
{
    /**
     * Creates a Connection mock that allows configuring quoteSingleIdentifier and quote.
     * Adds these methods when they don't exist on Connection (e.g. DBAL 2.x) so they can be configured.
     * Use this instead of createMock(Connection::class) when you need to stub these methods.
     */
    public static function createConnectionMock(TestCase $testCase): Connection
    {
        $methodsToAdd = [];
        $reflection   = new ReflectionClass(Connection::class);

        // Only add when the method does not exist (e.g. DBAL 2.x). If it exists and is final we cannot mock it.
        if (!$reflection->hasMethod('quoteSingleIdentifier')) {
            $methodsToAdd[] = 'quoteSingleIdentifier';
        }
        if (!$reflection->hasMethod('quote')) {
            $methodsToAdd[] = 'quote';
        }

        $builder = $testCase->getMockBuilder(Connection::class)
            ->disableOriginalConstructor();

        if ($methodsToAdd !== []) {
            $builder->addMethods(array_unique($methodsToAdd));
        }

        return $builder->getMock();
    }
}
