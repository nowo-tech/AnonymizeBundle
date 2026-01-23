<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\DependencyInjection;

use Nowo\AnonymizeBundle\DependencyInjection\AnonymizeExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Test case for AnonymizeExtension.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizeExtensionTest extends TestCase
{
    private AnonymizeExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new AnonymizeExtension();
    }

    /**
     * Test that getAlias returns the correct alias.
     */
    public function testGetAlias(): void
    {
        $alias = $this->extension->getAlias();
        $this->assertEquals('nowo_anonymize', $alias);
    }

    /**
     * Test that load sets default parameters when no config is provided.
     */
    public function testLoadSetsDefaultParameters(): void
    {
        $container = new ContainerBuilder(new ParameterBag());
        $configs = [];

        $this->extension->load($configs, $container);

        $this->assertEquals('en_US', $container->getParameter('nowo_anonymize.locale'));
        $this->assertEquals([], $container->getParameter('nowo_anonymize.connections'));
        $this->assertEquals(false, $container->getParameter('nowo_anonymize.dry_run'));
        $this->assertEquals(100, $container->getParameter('nowo_anonymize.batch_size'));
        $this->assertStringContainsString('var/stats', $container->getParameter('nowo_anonymize.stats_output_dir'));
        $this->assertStringContainsString('var/anonymize_history', $container->getParameter('nowo_anonymize.history_dir'));
    }

    /**
     * Test that load sets custom parameters when config is provided.
     */
    public function testLoadSetsCustomParameters(): void
    {
        $container = new ContainerBuilder(new ParameterBag());
        $configs = [
            [
                'locale' => 'es_ES',
                'connections' => ['default', 'custom'],
                'dry_run' => true,
                'batch_size' => 50,
                'stats_output_dir' => '/custom/stats',
                'history_dir' => '/custom/history',
            ],
        ];

        $this->extension->load($configs, $container);

        $this->assertEquals('es_ES', $container->getParameter('nowo_anonymize.locale'));
        $this->assertEquals(['default', 'custom'], $container->getParameter('nowo_anonymize.connections'));
        $this->assertEquals(true, $container->getParameter('nowo_anonymize.dry_run'));
        $this->assertEquals(50, $container->getParameter('nowo_anonymize.batch_size'));
        $this->assertEquals('/custom/stats', $container->getParameter('nowo_anonymize.stats_output_dir'));
        $this->assertEquals('/custom/history', $container->getParameter('nowo_anonymize.history_dir'));
    }

    /**
     * Test that load sets default export parameters when no export config is provided.
     */
    public function testLoadSetsDefaultExportParameters(): void
    {
        $container = new ContainerBuilder(new ParameterBag());
        $configs = [];

        $this->extension->load($configs, $container);

        $this->assertEquals(false, $container->getParameter('nowo_anonymize.export.enabled'));
        $this->assertStringContainsString('var/exports', $container->getParameter('nowo_anonymize.export.output_dir'));
        $this->assertEquals('{connection}_{database}_{date}_{time}.{format}', $container->getParameter('nowo_anonymize.export.filename_pattern'));
        $this->assertEquals('gzip', $container->getParameter('nowo_anonymize.export.compression'));
        $this->assertEquals([], $container->getParameter('nowo_anonymize.export.connections'));
        $this->assertEquals(true, $container->getParameter('nowo_anonymize.export.auto_gitignore'));
    }

    /**
     * Test that load sets custom export parameters when export config is provided.
     */
    public function testLoadSetsCustomExportParameters(): void
    {
        $container = new ContainerBuilder(new ParameterBag());
        $configs = [
            [
                'export' => [
                    'enabled' => true,
                    'output_dir' => '/custom/exports',
                    'filename_pattern' => 'backup_{database}.sql',
                    'compression' => 'zip',
                    'connections' => ['default'],
                    'auto_gitignore' => false,
                ],
            ],
        ];

        $this->extension->load($configs, $container);

        $this->assertEquals(true, $container->getParameter('nowo_anonymize.export.enabled'));
        $this->assertEquals('/custom/exports', $container->getParameter('nowo_anonymize.export.output_dir'));
        $this->assertEquals('backup_{database}.sql', $container->getParameter('nowo_anonymize.export.filename_pattern'));
        $this->assertEquals('zip', $container->getParameter('nowo_anonymize.export.compression'));
        $this->assertEquals(['default'], $container->getParameter('nowo_anonymize.export.connections'));
        $this->assertEquals(false, $container->getParameter('nowo_anonymize.export.auto_gitignore'));
    }

    /**
     * Test that load handles partial export configuration.
     */
    public function testLoadHandlesPartialExportConfiguration(): void
    {
        $container = new ContainerBuilder(new ParameterBag());
        $configs = [
            [
                'export' => [
                    'enabled' => true,
                    'compression' => 'bzip2',
                ],
            ],
        ];

        $this->extension->load($configs, $container);

        $this->assertEquals(true, $container->getParameter('nowo_anonymize.export.enabled'));
        $this->assertEquals('bzip2', $container->getParameter('nowo_anonymize.export.compression'));
        // Other export parameters should use defaults
        $this->assertStringContainsString('var/exports', $container->getParameter('nowo_anonymize.export.output_dir'));
        $this->assertEquals('{connection}_{database}_{date}_{time}.{format}', $container->getParameter('nowo_anonymize.export.filename_pattern'));
    }

    /**
     * Test that load handles multiple config arrays.
     */
    public function testLoadHandlesMultipleConfigArrays(): void
    {
        $container = new ContainerBuilder(new ParameterBag());
        $configs = [
            [
                'locale' => 'es_ES',
                'batch_size' => 50,
            ],
            [
                'locale' => 'fr_FR',
                'connections' => ['default'],
            ],
        ];

        $this->extension->load($configs, $container);

        // Last config should take precedence for locale
        $this->assertEquals('fr_FR', $container->getParameter('nowo_anonymize.locale'));
        // First config should be used for batch_size
        $this->assertEquals(50, $container->getParameter('nowo_anonymize.batch_size'));
        // Second config should be used for connections
        $this->assertEquals(['default'], $container->getParameter('nowo_anonymize.connections'));
    }
}
