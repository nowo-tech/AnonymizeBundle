<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\DependencyInjection\Compiler;

use Nowo\AnonymizeBundle\AnonymizeBundle;
use Nowo\AnonymizeBundle\DependencyInjection\AnonymizeExtension;
use Nowo\AnonymizeBundle\DependencyInjection\Compiler\AnonymizeServiceLocatorPass;
use Nowo\AnonymizeBundle\Enum\FakerType;
use Nowo\AnonymizeBundle\Faker\EmailFaker;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Tests\Unit\DependencyInjection\Compiler\Fixtures\DuckTypedFaker;
use Nowo\AnonymizeBundle\Tests\Unit\DependencyInjection\Compiler\Fixtures\EntityAnonymizer;
use Nowo\AnonymizeBundle\Tests\Unit\DependencyInjection\Compiler\Fixtures\InterfaceFaker;
use Nowo\AnonymizeBundle\Tests\Unit\DependencyInjection\Compiler\Fixtures\InvokableFaker;
use Nowo\AnonymizeBundle\Tests\Unit\DependencyInjection\Compiler\Fixtures\UnrelatedService;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

use function in_array;

/**
 * The anonymization plugin locator replaces the injected service container (REQ-DI-001).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class AnonymizeServiceLocatorPassTest extends TestCase
{
    public function testBundleRegistersThePass(): void
    {
        $container = new ContainerBuilder();
        (new AnonymizeBundle())->build($container);

        $passes = array_filter(
            $container->getCompilerPassConfig()->getBeforeOptimizationPasses(),
            static fn (object $pass): bool => $pass instanceof AnonymizeServiceLocatorPass,
        );
        self::assertCount(1, $passes);
    }

    public function testDoesNothingWithoutConsumers(): void
    {
        $container = new ContainerBuilder();
        $container->register('app.faker', InterfaceFaker::class);

        (new AnonymizeServiceLocatorPass())->process($container);

        self::assertSame(['service_container', 'app.faker'], array_keys($container->getDefinitions()));
    }

    public function testLocatorHoldsOnlyAnonymizationPlugins(): void
    {
        $container = $this->loadBundle();
        $container->register('app.interface_faker', InterfaceFaker::class);
        $container->setAlias('app.interface_faker_alias', 'app.interface_faker');
        $container->register('app.entity_anonymizer', EntityAnonymizer::class);
        $container->register('app.tagged_duck', DuckTypedFaker::class)->addTag(AnonymizeServiceLocatorPass::FAKER_TAG);
        $container->register('app.tagged_anonymizer', UnrelatedService::class)->addTag(AnonymizeServiceLocatorPass::ANONYMIZER_TAG);
        $container->register('app.public_duck', DuckTypedFaker::class)->setPublic(true);
        $container->register('app.public_invokable', InvokableFaker::class)->setPublic(true);
        $container->register('app.private_duck', DuckTypedFaker::class);
        $container->register('app.unrelated', UnrelatedService::class)->setPublic(true);
        $container->register('app.missing_class', 'App\\DoesNotExist')->setPublic(true);
        $container->register('app.abstract', InterfaceFaker::class)->setAbstract(true);
        $container->register('app.synthetic', InterfaceFaker::class)->setSynthetic(true);
        $container->register('app.parameter_class', '%app.faker_class%');
        $container->setParameter('app.faker_class', InterfaceFaker::class);
        $container->register('app.non_string_class', '%app.class_list%')->setPublic(true);
        $container->setParameter('app.class_list', [InterfaceFaker::class]);

        (new AnonymizeServiceLocatorPass())->process($container);

        $ids = $this->locatorIds($container, FakerFactory::SERVICE_NAME);
        foreach ([
            'nowo_anonymize.faker.email',
            EmailFaker::class,
            'app.interface_faker',
            'app.interface_faker_alias',
            'app.entity_anonymizer',
            'app.tagged_duck',
            'app.tagged_anonymizer',
            'app.public_duck',
            'app.public_invokable',
            'app.parameter_class',
        ] as $expected) {
            self::assertContains($expected, $ids, $expected);
        }
        foreach ([
            'service_container',
            FakerFactory::SERVICE_NAME,
            AnonymizeService::class,
            'app.private_duck',
            'app.unrelated',
            'app.missing_class',
            'app.abstract',
            'app.synthetic',
            'app.non_string_class',
        ] as $unexpected) {
            self::assertNotContains($unexpected, $ids, $unexpected);
        }

        // Both consumers share the same locator; nothing receives the whole container any more.
        self::assertEquals(
            $container->getDefinition(FakerFactory::SERVICE_NAME)->getArgument('$container'),
            $container->getDefinition(AnonymizeService::class)->getArgument('$container'),
        );
    }

    public function testCompiledFactoryResolvesBuiltInAndPrivateCustomFakers(): void
    {
        $container = $this->loadBundle();
        $container->register('app.interface_faker', InterfaceFaker::class);
        $container->register('app.unrelated', UnrelatedService::class)->setPublic(true);

        // Keep only what the factory needs so the compile does not require Doctrine & co.
        $keep = ['service_container', FakerFactory::SERVICE_NAME, EmailFaker::class, 'app.interface_faker', 'app.unrelated'];
        foreach (array_keys($container->getDefinitions()) as $id) {
            if (!in_array($id, $keep, true)) {
                $container->removeDefinition($id);
            }
        }
        foreach ($container->getAliases() as $alias => $target) {
            if (!in_array((string) $target, $keep, true)) {
                $container->removeAlias($alias);
            }
        }
        $container->addCompilerPass(new AnonymizeServiceLocatorPass());
        $container->compile();

        $factory = $container->get(FakerFactory::SERVICE_NAME);
        self::assertInstanceOf(FakerFactory::class, $factory);
        self::assertInstanceOf(EmailFaker::class, $factory->create(FakerType::EMAIL));
        self::assertSame('interface-faker', $factory->create(FakerType::SERVICE, 'app.interface_faker')->generate());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Service "app.unrelated" not found. Custom faker services must implement');
        $factory->create(FakerType::SERVICE, 'app.unrelated')->generate();
    }

    private function loadBundle(): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.project_dir' => sys_get_temp_dir(), 'kernel.secret' => 'test']));
        (new AnonymizeExtension())->load([], $container);
        // Plain ParameterBag (no env processors in these unit containers)
        $container->setParameter('nowo_anonymize.hash_preserve.key', 'REDACTED-TEST-KEY-0000000000000000000');

        return $container;
    }

    /**
     * @return list<string>
     */
    private function locatorIds(ContainerBuilder $container, string $consumerId): array
    {
        $reference = $container->getDefinition($consumerId)->getArgument('$container');
        self::assertInstanceOf(Reference::class, $reference);

        $locator = $container->getDefinition((string) $reference);
        $map     = $locator->getArgument(0);
        self::assertIsArray($map);
        foreach ($map as $argument) {
            self::assertInstanceOf(ServiceClosureArgument::class, $argument);
        }

        return array_map(strval(...), array_keys($map));
    }
}
