<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\DependencyInjection;

use Nowo\AnonymizeBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

/**
 * Test case for Configuration (bundle config tree).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class ConfigurationTest extends TestCase
{
    private Processor $processor;

    protected function setUp(): void
    {
        $this->processor = new Processor();
    }

    private function processConfiguration(array $configs): array
    {
        return $this->processor->processConfiguration(new Configuration(), $configs);
    }

    /**
     * Test that getConfigTreeBuilder returns a tree with the bundle alias as root.
     */
    public function testConfigTreeBuilderUsesAlias(): void
    {
        $configuration = new Configuration();
        $treeBuilder   = $configuration->getConfigTreeBuilder();

        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
        $this->assertSame(Configuration::ALIAS, $treeBuilder->buildTree()->getName());
    }

    /**
     * Test default values when no config is provided.
     */
    public function testDefaultConfiguration(): void
    {
        $config = $this->processConfiguration([[]]);

        $this->assertSame('en_US', $config['locale']);
        $this->assertSame([], $config['connections']);
        $this->assertFalse($config['dry_run']);
        $this->assertSame(100, $config['batch_size']);
        $this->assertSame('%kernel.project_dir%/var/stats', $config['stats_output_dir']);
        $this->assertSame('%kernel.project_dir%/var/anonymize_history', $config['history_dir']);
        $this->assertIsArray($config['export']);
        $this->assertArrayHasKey('enabled', $config['export']);
        $this->assertArrayHasKey('output_dir', $config['export']);
        $this->assertArrayHasKey('filename_pattern', $config['export']);
        $this->assertArrayHasKey('compression', $config['export']);
        $this->assertArrayHasKey('connections', $config['export']);
        $this->assertArrayHasKey('auto_gitignore', $config['export']);
    }

    /**
     * Test default export subsection.
     */
    public function testDefaultExportConfiguration(): void
    {
        $config = $this->processConfiguration([[]]);

        $this->assertFalse($config['export']['enabled']);
        $this->assertSame('%kernel.project_dir%/var/exports', $config['export']['output_dir']);
        $this->assertSame('{connection}_{database}_{date}_{time}.{format}', $config['export']['filename_pattern']);
        $this->assertSame('gzip', $config['export']['compression']);
        $this->assertSame([], $config['export']['connections']);
        $this->assertTrue($config['export']['auto_gitignore']);
    }

    /**
     * Test custom root options.
     */
    public function testCustomRootOptions(): void
    {
        $config = $this->processConfiguration([
            [
                'locale'           => 'es_ES',
                'connections'      => ['default', 'secondary'],
                'dry_run'          => true,
                'batch_size'       => 50,
                'stats_output_dir' => '/var/stats',
                'history_dir'      => '/var/history',
            ],
        ]);

        $this->assertSame('es_ES', $config['locale']);
        $this->assertSame(['default', 'secondary'], $config['connections']);
        $this->assertTrue($config['dry_run']);
        $this->assertSame(50, $config['batch_size']);
        $this->assertSame('/var/stats', $config['stats_output_dir']);
        $this->assertSame('/var/history', $config['history_dir']);
    }

    /**
     * Test custom export options.
     */
    public function testCustomExportConfiguration(): void
    {
        $config = $this->processConfiguration([
            [
                'export' => [
                    'enabled'          => true,
                    'output_dir'       => '/backups',
                    'filename_pattern' => 'dump_{database}.sql',
                    'compression'      => 'bzip2',
                    'connections'      => ['default'],
                    'auto_gitignore'   => false,
                ],
            ],
        ]);

        $this->assertTrue($config['export']['enabled']);
        $this->assertSame('/backups', $config['export']['output_dir']);
        $this->assertSame('dump_{database}.sql', $config['export']['filename_pattern']);
        $this->assertSame('bzip2', $config['export']['compression']);
        $this->assertSame(['default'], $config['export']['connections']);
        $this->assertFalse($config['export']['auto_gitignore']);
    }

    /**
     * Test that compression only accepts allowed values.
     */
    public function testExportCompressionAllowedValues(): void
    {
        foreach (['none', 'gzip', 'bzip2', 'zip'] as $compression) {
            $config = $this->processConfiguration([['export' => ['compression' => $compression]]]);
            $this->assertSame($compression, $config['export']['compression']);
        }
    }

    /**
     * Test configuration merge (multiple config arrays).
     */
    public function testConfigurationMerge(): void
    {
        $config = $this->processConfiguration([
            ['locale' => 'en_US', 'batch_size' => 100],
            ['locale' => 'fr_FR', 'connections' => ['default']],
        ]);

        $this->assertSame('fr_FR', $config['locale']);
        $this->assertSame(100, $config['batch_size']);
        $this->assertSame(['default'], $config['connections']);
    }

    /**
     * Test batch_size minimum constraint (1).
     */
    public function testBatchSizeMinimum(): void
    {
        $config = $this->processConfiguration([['batch_size' => 1]]);
        $this->assertSame(1, $config['batch_size']);
    }

    /**
     * Test that invalid export compression value triggers validation error.
     */
    public function testExportCompressionInvalidValueThrows(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        $this->processConfiguration([['export' => ['compression' => 'invalid']]]);
    }
}
