<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\DependencyInjection;

use InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use function class_exists;
use function is_array;
use function is_string;
use function ltrim;
use function sprintf;

/**
 * Extension for loading the bundle configuration.
 *
 * This extension loads the services configuration and processes the bundle configuration.
 * It registers all services defined in the services.yaml file.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AnonymizeExtension extends Extension
{
    /**
     * Loads the bundle configuration and services.
     *
     * @param array<string, mixed> $configs The configuration array
     * @param ContainerBuilder $container The container builder
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = $this->getConfiguration($configs, $container);
        $config        = $this->processConfiguration($configuration, $configs);

        // Set configuration parameters
        $container->setParameter('nowo_anonymize.locale', $config['locale'] ?? 'en_US');
        $container->setParameter('nowo_anonymize.connections', $config['connections'] ?? []);
        $container->setParameter('nowo_anonymize.dry_run', $config['dry_run'] ?? false);
        $container->setParameter('nowo_anonymize.batch_size', $config['batch_size'] ?? 100);
        $container->setParameter('nowo_anonymize.stats_output_dir', $config['stats_output_dir'] ?? '%kernel.project_dir%/var/stats');
        $container->setParameter('nowo_anonymize.history_dir', $config['history_dir'] ?? '%kernel.project_dir%/var/anonymize_history');

        // Export configuration
        $exportConfig = $config['export'] ?? [];
        $container->setParameter('nowo_anonymize.export.enabled', $exportConfig['enabled'] ?? false);
        $container->setParameter('nowo_anonymize.export.output_dir', $exportConfig['output_dir'] ?? '%kernel.project_dir%/var/exports');
        $container->setParameter('nowo_anonymize.export.filename_pattern', $exportConfig['filename_pattern'] ?? '{connection}_{database}_{date}_{time}.{format}');
        $container->setParameter('nowo_anonymize.export.compression', $exportConfig['compression'] ?? 'gzip');
        $container->setParameter('nowo_anonymize.export.connections', $exportConfig['connections'] ?? []);
        $container->setParameter('nowo_anonymize.export.auto_gitignore', $exportConfig['auto_gitignore'] ?? true);
        $container->setParameter('nowo_anonymize.export.timeout', $exportConfig['timeout'] ?? 180);

        $container->setParameter('nowo_anonymize.entities', $this->validateEntities($config['entities'] ?? []));

        $protectionConfig = $config['environment_protection'] ?? [];
        $container->setParameter(
            'nowo_anonymize.environment_protection.blocked_dsn_substrings',
            $protectionConfig['blocked_dsn_substrings'] ?? [],
        );

        // Dedicated HMAC key for hash_preserve; kernel.secret is no longer reused (it signs other things)
        $hashPreserveConfig = $config['hash_preserve'] ?? [];
        $key                = $hashPreserveConfig['key'] ?? $hashPreserveConfig['default_salt'] ?? null;
        $container->setParameter(
            'nowo_anonymize.hash_preserve.key',
            is_string($key) && $key !== '' ? $key : '%env(default::NOWO_ANONYMIZE_HASH_KEY)%',
        );
    }

    /**
     * Since 1.5: every configured entity must be an existing class, and patterns / options must be arrays.
     *
     * @param array<string, array<string, mixed>> $entities
     *
     * @return array<string, array<string, mixed>>
     */
    private function validateEntities(array $entities): array
    {
        $valid = [];
        foreach ($entities as $className => $entity) {
            $className = ltrim($className, '\\');
            if (!class_exists($className)) {
                throw new InvalidArgumentException(sprintf('The "nowo_anonymize.entities" key "%s" is not an existing class.', $className));
            }

            // class_exists() ignores case, the selection does not (audit LQ-N-L2): a mis-cased key would select nothing.
            $canonical = (new ReflectionClass($className))->getName();
            if ($canonical !== $className) {
                throw new InvalidArgumentException(sprintf('The "nowo_anonymize.entities" key "%s" must be written exactly as the class name "%s".', $className, $canonical));
            }

            $this->assertArrays($entity, ['include_patterns', 'exclude_patterns'], $className);
            foreach ($entity['properties'] ?? [] as $field => $property) {
                $this->assertArrays($property, ['include_patterns', 'exclude_patterns', 'options'], $className . '.properties.' . $field);
            }

            $valid[$className] = $entity;
        }

        return $valid;
    }

    /**
     * @param array<string, mixed> $config
     * @param list<string> $keys
     */
    private function assertArrays(array $config, array $keys, string $path): void
    {
        foreach ($keys as $key) {
            if (isset($config[$key]) && !is_array($config[$key])) {
                throw new InvalidArgumentException(sprintf('The "nowo_anonymize.entities.%s.%s" option must be an array.', $path, $key));
            }
        }
    }

    /**
     * Returns the extension alias.
     *
     * @return string The extension alias
     */
    public function getAlias(): string
    {
        return Configuration::ALIAS;
    }
}
