<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\DependencyInjection;

use Nowo\AnonymizeBundle\DependencyInjection\AnonymizeExtension;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Faker\FakerFactoryInterface;
use Nowo\AnonymizeBundle\Tests\Integration\DependencyInjection\Fixtures\FakerFactoryConsumer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

use function in_array;

/**
 * App services can autowire FakerFactory / FakerFactoryInterface by type.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class FakerFactoryAutowiringTest extends TestCase
{
    public function testFakerFactoryClassAndInterfaceAreAliasedToTheBundleService(): void
    {
        $container = $this->loadBundle();

        self::assertTrue($container->hasAlias(FakerFactory::class));
        self::assertSame(FakerFactory::SERVICE_NAME, (string) $container->getAlias(FakerFactory::class));
        self::assertTrue($container->hasAlias(FakerFactoryInterface::class));
        self::assertSame(FakerFactory::SERVICE_NAME, (string) $container->getAlias(FakerFactoryInterface::class));
    }

    public function testAppServiceAutowiresFakerFactoryByType(): void
    {
        $container = $this->loadBundle();
        $container->register(FakerFactoryConsumer::class)->setAutowired(true)->setPublic(true);

        // Keep only the factory and the consumer so the compile does not need Doctrine & co.
        foreach (array_keys($container->getDefinitions()) as $id) {
            if (!in_array($id, ['service_container', FakerFactory::SERVICE_NAME, FakerFactoryConsumer::class], true)) {
                $container->removeDefinition($id);
            }
        }
        foreach (array_keys($container->getAliases()) as $alias) {
            if (!in_array($alias, [FakerFactory::class, FakerFactoryInterface::class], true)) {
                $container->removeAlias($alias);
            }
        }

        $container->compile();

        $consumer = $container->get(FakerFactoryConsumer::class);
        self::assertInstanceOf(FakerFactoryConsumer::class, $consumer);
        self::assertSame($container->get(FakerFactory::SERVICE_NAME), $consumer->fakerFactory);
        self::assertSame($consumer->fakerFactory, $consumer->fakerFactoryInterface);
    }

    private function loadBundle(): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.project_dir' => sys_get_temp_dir(), 'kernel.secret' => 'test']));
        (new AnonymizeExtension())->load([], $container);

        return $container;
    }
}
