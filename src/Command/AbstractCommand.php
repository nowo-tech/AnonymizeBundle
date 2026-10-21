<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Command;

use Doctrine\DBAL\Connection;
use Nowo\AnonymizeBundle\Helper\DbalHelper;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Throwable;

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
     * @param Connection $connection The database connection
     * @param string $identifier The identifier to quote
     *
     * @return string The quoted identifier
     */
    protected function quoteIdentifier(Connection $connection, string $identifier): string
    {
        return DbalHelper::quoteIdentifier($connection, $identifier);
    }

    /**
     * Runs a destructive command body and logs start, success and failure (REQ-OBS-001).
     *
     * Only the command name, the action and non-sensitive options (connections, dry-run) are logged: never DSNs,
     * credentials or row data.
     *
     * @param callable(): int $body
     * @param array<string, mixed> $context
     *
     * @return int The command exit code
     */
    protected function runLogged(?LoggerInterface $logger, string $action, array $context, callable $body): int
    {
        $logger ??= new NullLogger();
        $context = ['bundle' => 'anonymize', 'action' => $action, 'command' => $this->getName()] + $context;
        $logger->info('Anonymize command started.', $context);

        try {
            $exit = $body();
        } catch (Throwable $e) {
            $logger->error('Anonymize command failed.', $context + ['exception' => $e::class]);

            throw $e;
        }

        if ($exit === self::SUCCESS) {
            $logger->info('Anonymize command finished.', $context);
        } else {
            $logger->warning('Anonymize command did not complete.', $context + ['exit_code' => $exit]);
        }

        return $exit;
    }
}
