<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Kernel;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\Persistence\AbstractManagerRegistry;
use Doctrine\Persistence\Proxy;
use LogicException;

use const PHP_VERSION_ID;

/**
 * A DDD host's Doctrine wiring without DoctrineBundle: one entity manager on a SQLite file with the XML mapping of
 * tests/Integration/Ddd (plain-PHP domain, no attribute).
 */
final class TestManagerRegistry extends AbstractManagerRegistry
{
    private ?EntityManager $entityManager = null;

    public function __construct(private readonly string $databasePath, private readonly string $proxyDir)
    {
        parent::__construct('ORM', ['default' => 'default'], ['default' => 'default'], 'default', 'default', Proxy::class);
    }

    protected function getService(string $name): EntityManager
    {
        return $this->entityManager ??= $this->createEntityManager();
    }

    protected function resetService(string $name): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function getAliasNamespace(string $alias): string
    {
        throw new LogicException('Namespace aliases are not supported.');
    }

    private function createEntityManager(): EntityManager
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(new SimplifiedXmlDriver([__DIR__ . '/../Ddd/config/doctrine' => 'Nowo\\AnonymizeBundle\\Tests\\Integration\\Ddd\\Domain']));
        if (PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        } else {
            $config->setProxyDir($this->proxyDir);
            $config->setProxyNamespace('AnonymizeKernelProxies');
            $config->setAutoGenerateProxyClasses(true);
        }

        return new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => $this->databasePath], $config), $config);
    }
}
