<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use function in_array;
use function is_array;
use function sprintf;

/**
 * Service for enhanced environment protection checks.
 *
 * Provides comprehensive environment validation to prevent accidental
 * execution in production or unsafe environments.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class EnvironmentProtectionService
{
    /**
     * Creates a new EnvironmentProtectionService instance.
     *
     * @param ParameterBagInterface $parameterBag The parameter bag for accessing kernel parameters
     */
    public function __construct(
        private ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * Performs comprehensive environment protection checks.
     *
     * @return array<string, string> Array of error messages (empty if all checks pass)
     */
    public function performChecks(): array
    {
        $errors = [];

        // Check environment
        $errors = array_merge($errors, $this->checkEnvironment());

        // Check debug mode
        $errors = array_merge($errors, $this->checkDebugMode());

        // Check configuration files
        $errors = array_merge($errors, $this->checkConfigurationFiles());

        return $errors;
    }

    /**
     * Checks if the environment is safe for anonymization.
     *
     * @return array<string> Array of error messages
     */
    private function checkEnvironment(): array
    {
        $errors      = [];
        $environment = $this->parameterBag->get('kernel.environment');

        if (!in_array($environment, ['dev', 'test'], true)) {
            $errors[] = sprintf(
                'Unsafe environment detected: "%s". Anonymization can only run in "dev" or "test" environments.',
                $environment,
            );
        }

        return $errors;
    }

    /**
     * Checks if debug mode is enabled (additional safety check).
     *
     * @return array<string> Array of error messages
     */
    private function checkDebugMode(): array
    {
        $errors = [];
        $debug  = $this->parameterBag->get('kernel.debug');

        // In production-like environments, debug should be false
        // But we allow it in dev/test for development purposes
        $environment = $this->parameterBag->get('kernel.environment');
        if (!in_array($environment, ['dev', 'test'], true) && $debug === false) {
            // This is actually good for production, but we don't allow anonymization anyway
            // This check is more of a warning
        }

        return $errors;
    }

    /**
     * Checks configuration files for production settings.
     *
     * @return array<string> Array of error messages
     */
    private function checkConfigurationFiles(): array
    {
        $errors     = [];
        $projectDir = $this->parameterBag->get('kernel.project_dir');

        // Check if bundle is configured in production config
        $prodConfigPath = $projectDir . '/config/packages/prod/nowo_anonymize.yaml';
        if (file_exists($prodConfigPath)) {
            $errors[] = sprintf(
                'Production configuration file detected: %s. This bundle should not be configured for production environments.',
                $prodConfigPath,
            );
        }

        // Check if bundle is registered in bundles.php for production
        $bundlesPath = $projectDir . '/config/bundles.php';
        if (file_exists($bundlesPath)) {
            $bundles     = require $bundlesPath;
            $bundleClass = 'Nowo\\AnonymizeBundle\\AnonymizeBundle';
            if (isset($bundles[$bundleClass])) {
                $allowedEnvs = $bundles[$bundleClass];
                if (is_array($allowedEnvs) && isset($allowedEnvs['prod']) && $allowedEnvs['prod'] === true) {
                    $errors[] = sprintf(
                        'Bundle is registered for production environment in %s. This bundle should only be enabled for "dev" and "test" environments.',
                        $bundlesPath,
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * Gets the current environment name.
     *
     * @return string The environment name
     */
    public function getEnvironment(): string
    {
        return $this->parameterBag->get('kernel.environment');
    }

    /**
     * Checks if the current environment is safe for anonymization.
     *
     * @return bool True if safe, false otherwise
     */
    public function isSafeEnvironment(): bool
    {
        $environment = $this->getEnvironment();

        return in_array($environment, ['dev', 'test'], true);
    }
}
