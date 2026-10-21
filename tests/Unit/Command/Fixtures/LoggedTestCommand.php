<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Command\Fixtures;

use Nowo\AnonymizeBundle\Command\AbstractCommand;
use Psr\Log\LoggerInterface;

/**
 * Exposes {@see AbstractCommand::runLogged()} to tests.
 */
final class LoggedTestCommand extends AbstractCommand
{
    public function __construct()
    {
        parent::__construct('test:logged');
    }

    /**
     * @param array<string, mixed> $context
     * @param callable(): int $body
     */
    public function runPublic(?LoggerInterface $logger, string $action, array $context, callable $body): int
    {
        return $this->runLogged($logger, $action, $context, $body);
    }
}
