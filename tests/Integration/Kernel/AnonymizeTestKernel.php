<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Kernel;

use Closure;
use Doctrine\Persistence\ManagerRegistry;
use Nowo\AnonymizeBundle\AnonymizeBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
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
}
