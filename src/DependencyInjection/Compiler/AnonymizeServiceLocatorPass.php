<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\DependencyInjection\Compiler;

use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Faker\FakerInterface;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\EntityAnonymizerServiceInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use function is_string;

/**
 * Builds the closed registry of anonymization plugins (REQ-DI-001).
 *
 * FakerFactory (built-in fakers + `type: 'service'` custom fakers) and AnonymizeService
 * (`#[Anonymize(anonymizeService: ...)]`) resolve services by id at runtime, because the ids
 * come from entity attributes. Instead of injecting the whole service container, they receive
 * a service locator restricted to:
 *
 * - every service whose class implements {@see FakerInterface} or {@see EntityAnonymizerServiceInterface}
 *   (any visibility; private services work too);
 * - every service tagged {@see self::FAKER_TAG} or {@see self::ANONYMIZER_TAG};
 * - for backward compatibility, every public service whose class exposes a public `generate()`
 *   or `__invoke()` method (the documented duck-typed custom faker contract);
 * - every alias pointing to one of the above (e.g. `nowo_anonymize.faker.email`).
 *
 * Services are referenced lazily, so nothing is instantiated until an entity asks for it.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AnonymizeServiceLocatorPass implements CompilerPassInterface
{
    /** Tag for custom faker services that do not implement FakerInterface (generate()/__invoke duck typing). */
    public const FAKER_TAG = 'nowo_anonymize.faker';

    /** Tag for entity anonymizer services (implementing EntityAnonymizerServiceInterface is enough). */
    public const ANONYMIZER_TAG = 'nowo_anonymize.entity_anonymizer';

    /** Service ids whose `$container` argument receives the plugin locator. */
    private const CONSUMERS = ['nowo_anonymize.faker_factory', AnonymizeService::class];

    /**
     * Registers the plugin locator and injects it into the consumers.
     */
    public function process(ContainerBuilder $container): void
    {
        $consumers = [];
        foreach (self::CONSUMERS as $id) {
            if ($container->hasDefinition($id)) {
                $consumers[] = $container->getDefinition($id);
            }
        }
        if ($consumers === []) {
            return;
        }

        $ids = [];
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($this->isPlugin($container, $id, $definition)) {
                $ids[$id] = true;
            }
        }

        $references = [];
        foreach (array_keys($ids) as $id) {
            $references[$id] = new Reference($id);
        }
        foreach ($container->getAliases() as $aliasId => $alias) {
            if (isset($ids[(string) $alias])) {
                $references[$aliasId] = new Reference($aliasId);
            }
        }
        ksort($references);

        $locator = ServiceLocatorTagPass::register($container, $references);
        foreach ($consumers as $definition) {
            $definition->setArgument('$container', $locator);
        }
    }

    /**
     * Whether a service definition belongs to the anonymization plugin registry.
     */
    private function isPlugin(ContainerBuilder $container, string $id, Definition $definition): bool
    {
        if ($definition->isAbstract() || $definition->isSynthetic()) {
            return false;
        }
        if ($definition->hasTag(self::FAKER_TAG) || $definition->hasTag(self::ANONYMIZER_TAG)) {
            return true;
        }

        $class = $container->getParameterBag()->resolveValue($definition->getClass() ?? $id);
        if (!is_string($class) || $class === FakerFactory::class) {
            return false;
        }

        $reflection = $container->getReflectionClass($class, false);
        if (!$reflection instanceof ReflectionClass) {
            return false;
        }
        if ($reflection->implementsInterface(FakerInterface::class) || $reflection->implementsInterface(EntityAnonymizerServiceInterface::class)) {
            return true;
        }

        return $definition->isPublic() && $this->hasPublicMethod($reflection, 'generate', '__invoke');
    }

    /**
     * Whether the class declares at least one of the given public methods.
     *
     * @param ReflectionClass<object> $reflection
     */
    private function hasPublicMethod(ReflectionClass $reflection, string ...$methods): bool
    {
        foreach ($methods as $method) {
            if ($reflection->hasMethod($method) && $reflection->getMethod($method)->isPublic()) {
                return true;
            }
        }

        return false;
    }
}
