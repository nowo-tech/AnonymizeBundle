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
        $rootNode    = $treeBuilder->getRootNode();

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
                ->scalarNode('stats_output_dir')
                    ->info('Directory where statistics exports will be saved (JSON/CSV)')
                    ->defaultValue('%kernel.project_dir%/var/stats')
                ->end()
                ->scalarNode('history_dir')
                    ->info('Directory where anonymization history will be stored')
                    ->defaultValue('%kernel.project_dir%/var/anonymize_history')
                ->end()
                ->arrayNode('export')
                    ->info('Database export configuration')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('output_dir')
                            ->info('Directory where database exports will be saved')
                            ->defaultValue('%kernel.project_dir%/var/exports')
                        ->end()
                        ->scalarNode('filename_pattern')
                            ->info('Filename pattern for exports. Available placeholders: {connection}, {database}, {date}, {time}, {format}')
                            ->defaultValue('{connection}_{database}_{date}_{time}.{format}')
                        ->end()
                        ->enumNode('compression')
                            ->info('Compression format: none, gzip, bzip2, zip')
                            ->values(['none', 'gzip', 'bzip2', 'zip'])
                            ->defaultValue('gzip')
                        ->end()
                        ->arrayNode('connections')
                            ->info('List of connections to export. If empty, all connections will be exported')
                            ->prototype('scalar')->end()
                            ->defaultValue([])
                        ->end()
                        ->booleanNode('auto_gitignore')
                            ->info('Automatically create/update .gitignore to exclude export directory')
                            ->defaultValue(true)
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
