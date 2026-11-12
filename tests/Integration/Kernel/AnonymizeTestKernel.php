<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Kernel;

use Closure;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\AnonymizeBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Kernel of a DDD host: FrameworkBundle and the bundle, the host's own ManagerRegistry over an XML-mapped domain
 * (no DoctrineBundle, no attribute on the domain) and the bundle configured through `nowo_anonymize.entities`.
 */
final class AnonymizeTestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @param Closure(ContainerConfigurator): void $configure bundle configuration and host services
     */
    public function __construct(private readonly string $baseDir, private readonly Closure $configure, bool $debug = true)
    {
        parent::__construct('test', $debug);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new AnonymizeBundle();
    }

    public function getProjectDir(): string
    {
        return $this->baseDir;
    }

    public function getCacheDir(): string
    {
        return $this->baseDir . '/cache';
    }

    public function getLogDir(): string
    {
        return $this->baseDir . '/log';
    }

    public function databasePath(): string
    {
        return $this->baseDir . '/db.sqlite';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret'                => 'REDACTED-TEST-kernel',
            'test'                  => true,
            'http_method_override'  => false,
            'handle_all_throwables' => true,
            'php_errors'            => ['log' => true],
        ]);
        $services = $container->services();
        $services->set('logger', NullLogger::class);
        $services->set(ManagerRegistry::class, TestManagerRegistry::class)
            ->args([$this->databasePath(), $this->baseDir . '/proxies'])
            ->public();
        ($this->configure)($container);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
    }

    protected function build(ContainerBuilder $container): void
    {
        // The CI matrix pins symfony/yaml (e.g. 6.4) while symfony/console floats (8.x): yaml's LintCommand then cannot
        // be loaded, and lint:container reflects every service class. The host needs no YAML lint command here.
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                $container->removeDefinition('console.command.yaml_lint');
            }
        }, PassConfig::TYPE_BEFORE_OPTIMIZATION, -1024);
    }
}
