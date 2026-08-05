<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\AnonymizeBundle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use function in_array;
use function is_array;
use function is_string;
use function parse_url;
use function sprintf;
use function str_contains;
use function strtolower;

/**
 * Environment and DSN guards that block anonymize/export CLI outside safe contexts.
 *
 * Combines kernel environment checks with a configurable DSN/host denylist so
 * `bin/console --env=dev` against a production database is rejected (REQ-SEC-004).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final readonly class EnvironmentProtectionService
{
    /**
     * @param list<string> $blockedDsnSubstrings Case-insensitive substrings matched against connection URLs and hosts
     */
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private array $blockedDsnSubstrings = [],
        private ?ManagerRegistry $doctrine = null,
    ) {
    }

    /**
     * @return list<string> Error messages (empty if all checks pass)
     */
    public function performChecks(): array
    {
        $errors = [];
        $errors = array_merge($errors, $this->checkEnvironment());
        $errors = array_merge($errors, $this->checkConfigurationFiles());
        $errors = array_merge($errors, $this->checkDatabaseUrls());

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function checkEnvironment(): array
    {
        $errors      = [];
        $environment = $this->getStringParameter('kernel.environment');

        if (!in_array($environment, ['dev', 'test'], true)) {
            $errors[] = sprintf(
                'Unsafe environment detected: "%s". Anonymization can only run in "dev" or "test" environments.',
                $environment,
            );
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function checkConfigurationFiles(): array
    {
        $errors     = [];
        $projectDir = $this->getStringParameter('kernel.project_dir');

        $prodConfigPath = $projectDir . '/config/packages/prod/nowo_anonymize.yaml';
        if (file_exists($prodConfigPath)) {
            $errors[] = sprintf(
                'Production configuration file detected: %s. This bundle should not be configured for production environments.',
                $prodConfigPath,
            );
        }

        $bundlesPath = $projectDir . '/config/bundles.php';
        if (file_exists($bundlesPath)) {
            $bundles     = require $bundlesPath;
            $bundleClass = AnonymizeBundle::class;
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
     * Rejects DATABASE_URL / MONGODB_URL (and similar) and Doctrine connection params that look like production DSNs.
     *
     * @return list<string>
     */
    private function checkDatabaseUrls(): array
    {
        if ($this->blockedDsnSubstrings === []) {
            return [];
        }

        $errors     = [];
        $candidates = [];

        foreach (['DATABASE_URL', 'DATABASE_URL_DEFAULT', 'MONGODB_URL', 'MONGODB_URI'] as $envKey) {
            $value = $_SERVER[$envKey] ?? getenv($envKey);
            if (is_string($value) && $value !== '') {
                $candidates[$envKey] = $value;
            }
        }

        foreach ($candidates as $envKey => $url) {
            $errors = array_merge($errors, $this->matchBlockedMarkers($envKey, $this->haystacksFromUrl($url)));
        }

        if ($this->doctrine !== null) {
            foreach ($this->doctrine->getConnections() as $name => $connection) {
                if (!$connection instanceof Connection) {
                    continue;
                }

                /** @var array<string, mixed> $params */
                $params    = $connection->getParams();
                $haystacks = $this->haystacksFromDoctrineParams($params);
                if ($haystacks === []) {
                    continue;
                }

                $label  = sprintf('Doctrine connection "%s"', is_string($name) ? $name : (string) $name);
                $errors = array_merge($errors, $this->matchBlockedMarkers($label, $haystacks));
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return list<string>
     */
    private function haystacksFromDoctrineParams(array $params): array
    {
        $haystacks = [];

        foreach (['url', 'host', 'dbname', 'path', 'user'] as $key) {
            $value = $params[$key] ?? null;
            if (is_string($value) && $value !== '') {
                $haystacks[] = $value;
            }
        }

        $primary = $params['primary'] ?? null;
        if (is_array($primary)) {
            $haystacks = array_merge($haystacks, $this->haystacksFromDoctrineParams($primary));
        }

        return $haystacks;
    }

    /**
     * @return list<string>
     */
    private function haystacksFromUrl(string $url): array
    {
        $haystacks = [$url];
        $parts     = parse_url($url);
        if (!is_array($parts)) {
            return $haystacks;
        }

        $host = $parts['host'] ?? null;
        if (is_string($host) && $host !== '') {
            $haystacks[] = $host;
        }
        $path = $parts['path'] ?? null;
        if (is_string($path) && $path !== '') {
            $haystacks[] = $path;
        }

        return $haystacks;
    }

    /**
     * @param list<string> $haystacks
     *
     * @return list<string>
     */
    private function matchBlockedMarkers(string $label, array $haystacks): array
    {
        $errors = [];

        foreach ($this->blockedDsnSubstrings as $needle) {
            if ($needle === '') {
                continue;
            }
            $needleLower = strtolower($needle);
            foreach ($haystacks as $haystack) {
                if (str_contains(strtolower($haystack), $needleLower)) {
                    $errors[] = sprintf(
                        'Blocked connection marker "%s" found in %s. Refusing to run against a denylisted DSN/host (configure nowo_anonymize.environment_protection.blocked_dsn_substrings or clear production URLs).',
                        $needle,
                        $label,
                    );

                    return $errors;
                }
            }
        }

        return $errors;
    }

    public function getEnvironment(): string
    {
        return $this->getStringParameter('kernel.environment');
    }

    public function isSafeEnvironment(): bool
    {
        return in_array($this->getEnvironment(), ['dev', 'test'], true);
    }

    private function getStringParameter(string $name): string
    {
        if (!$this->parameterBag->has($name)) {
            return '';
        }

        $value = $this->parameterBag->get($name);

        return is_string($value) ? $value : '';
    }
}
