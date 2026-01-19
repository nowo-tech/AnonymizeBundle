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
