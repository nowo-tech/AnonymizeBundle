<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration class for the bundle.
 *
 * Defines the configuration structure for the AnonymizeBundle.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * The extension alias.
     */
    public const ALIAS = 'nowo_anonymize';

    /**
     * Builds the configuration tree.
     *
     * @return TreeBuilder The configuration tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ALIAS);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('locale')
                    ->info('Locale for Faker generator (e.g., en_US, es_ES)')
                    ->defaultValue('en_US')
                ->end()
                ->arrayNode('connections')
                    ->info('Doctrine connections to process. If empty, all connections will be processed')
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
                ->booleanNode('dry_run')
                    ->info('If true, only show what would be anonymized without making changes')
                    ->defaultValue(false)
                ->end()
                ->integerNode('batch_size')
                    ->info('Number of records to process in each batch')
                    ->defaultValue(100)
                    ->min(1)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
