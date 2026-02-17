<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Command;

use Nowo\AnonymizeBundle\Helper\DbalHelper;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * Abstract base command for AnonymizeBundle commands.
 *
 * Provides common functionality and helper methods for all bundle commands.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
abstract class AbstractCommand extends SymfonyCommand
{
    /**
     * Quotes a single identifier (table or column name) for use in SQL queries.
     * Compatible with both DBAL 2.x and 3.x.
     *
     * @param \Doctrine\DBAL\Connection $connection The database connection
     * @param string $identifier The identifier to quote
     *
     * @return string The quoted identifier
     */
    protected function quoteIdentifier(\Doctrine\DBAL\Connection $connection, string $identifier): string
    {
        return DbalHelper::quoteIdentifier($connection, $identifier);
    }
}
