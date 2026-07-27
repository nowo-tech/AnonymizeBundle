<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Command;

use Nowo\AnonymizeBundle\Service\EnvironmentProtectionService;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

/**
 * Shared environment / DSN guard for anonymize CLI commands (REQ-SEC-004).
 */
trait EnvironmentProtectedCommandTrait
{
    /**
     * @return int|null Exit code when blocked; null when checks pass
     */
    private function failIfEnvironmentUnsafe(SymfonyStyle $io, EnvironmentProtectionService $environmentProtection): ?int
    {
        $protectionErrors = $environmentProtection->performChecks();
        if ($protectionErrors === []) {
            return null;
        }

        $io->error('Environment protection checks failed:');
        foreach ($protectionErrors as $error) {
            $io->writeln(sprintf('  - %s', $error));
        }
        $io->warning('This bundle is intended for development purposes only.');

        return self::FAILURE;
    }
}
