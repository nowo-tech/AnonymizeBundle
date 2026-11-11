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
                        ->integerNode('timeout')
                            ->info('Hard timeout in seconds for mysqldump/pg_dump/mongodump/compression subprocesses (REQ-RUNTIME-001). Keep below PHP max_execution_time and Caddy write timeout.')
                            ->defaultValue(180)
                            ->min(1)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('entities')
                    ->info('Since 1.5: entities anonymized without attributes (plain-PHP / XML-mapped domain), keyed by FQCN. Same settings as #[Anonymize] and #[AnonymizeProperty]; an attribute on the class or on a field wins over this configuration.')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('class')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('connection')->defaultNull()->info('Doctrine connection name (null: every processed connection).')->end()
                            ->variableNode('include_patterns')->defaultValue([])->info('Same format as #[Anonymize(includePatterns: ...)].')->end()
                            ->variableNode('exclude_patterns')->defaultValue([])->info('Same format as #[Anonymize(excludePatterns: ...)].')->end()
                            ->scalarNode('anonymize_service')->defaultNull()->info('Service id implementing EntityAnonymizerServiceInterface (replaces the properties).')->end()
                            ->booleanNode('truncate')->defaultFalse()->end()
                            ->integerNode('truncate_order')->defaultNull()->end()
                            ->booleanNode('mark_anonymized')
                                ->info('Set the table\'s "anonymized" column (when it exists) on every anonymized row, as AnonymizableTrait does.')
                                ->defaultFalse()
                            ->end()
                            ->arrayNode('properties')
                                ->info('Fields to anonymize, keyed by Doctrine field name ("embedProperty.field" for an embedded value object).')
                                ->normalizeKeys(false)
                                ->useAttributeAsKey('field')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('type')->isRequired()->cannotBeEmpty()->info('Faker type (see FakerType), e.g. email, name, phone, iban, service.')->end()
                                        ->integerNode('weight')->defaultNull()->end()
                                        ->variableNode('include_patterns')->defaultValue([])->end()
                                        ->variableNode('exclude_patterns')->defaultValue([])->end()
                                        ->scalarNode('service')->defaultNull()->info('Service id when type is "service".')->end()
                                        ->variableNode('options')->defaultValue([])->info('Faker options, as #[AnonymizeProperty(options: ...)].')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('hash_preserve')
                    ->info('HashPreserveFaker: keyed HMAC pseudonymisation (deterministic, so the output is pseudonymous, not anonymous)')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('key')
                            ->info('Secret HMAC key (at least 32 characters, never committed). Null reads the NOWO_ANONYMIZE_HASH_KEY environment variable. hash_preserve fails without a usable key.')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('default_salt')
                            ->info('Deprecated alias of "key".')
                            ->setDeprecated('nowo-tech/anonymize-bundle', '1.3.0', 'The "%node%" option at path "%path%" is deprecated, use "key" (a dedicated secret, e.g. %env(NOWO_ANONYMIZE_HASH_KEY)%) instead.')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('environment_protection')
                    ->info('Extra guards so CLI cannot run against production-like DSNs even with --env=dev (REQ-SEC-004)')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('blocked_dsn_substrings')
                            ->info('Case-insensitive substrings matched against DATABASE_URL / MONGODB_URL, Doctrine connection url/host/dbname, and hosts')
                            ->prototype('scalar')->end()
                            ->defaultValue([
                                '://prod',
                                '@prod.',
                                '.prod.',
                                'prod.',
                                'production.',
                                '/production',
                                'live.',
                                '.live.',
                                '@live.',
                            ])
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
