<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

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
        $config = $this->processConfiguration($configuration, $configs);

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
