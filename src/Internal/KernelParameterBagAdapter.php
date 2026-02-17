<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Internal;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use UnitEnum;

use function sprintf;

/**
 * Fallback parameter bag that reads parameters from the kernel's container via reflection.
 * Used when the application does not expose the parameter_bag service (e.g. in tests or non-standard setups).
 *
 * @internal This class is for bundle use only. Do not rely on it in application code.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class KernelParameterBagAdapter implements ParameterBagInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $name): array|bool|string|int|float|UnitEnum|null
    {
        // Prefer container parameters when available (avoids synthetic kernel service)
        if (method_exists($this->container, 'hasParameter') && method_exists($this->container, 'getParameter')
            && $this->container->hasParameter($name)) {
            return $this->container->getParameter($name);
        }

        if (!$this->container->has('kernel')) {
            throw new InvalidArgumentException(sprintf('Parameter "%s" not found', $name));
        }

        $kernel          = $this->container->get('kernel');
        $reflection      = new ReflectionClass($kernel);
        $kernelContainer = null;

        if ($reflection->hasProperty('container')) {
            $property = $reflection->getProperty('container');
            $property->setAccessible(true);
            $kernelContainer = $property->getValue($kernel);
        }

        if ($kernelContainer instanceof \Symfony\Component\DependencyInjection\Container) {
            if (method_exists($kernelContainer, 'getParameterBag')) {
                $paramBag = $kernelContainer->getParameterBag();
                if ($paramBag instanceof ParameterBagInterface) {
                    return $paramBag->get($name);
                }
            }
            $paramReflection = new ReflectionClass($kernelContainer);
            if ($paramReflection->hasProperty('parameterBag')) {
                $paramProperty = $paramReflection->getProperty('parameterBag');
                $paramProperty->setAccessible(true);
                $paramBag = $paramProperty->getValue($kernelContainer);
                if ($paramBag instanceof ParameterBagInterface) {
                    return $paramBag->get($name);
                }
            }
            if (method_exists($kernelContainer, 'getParameter')) {
                return $kernelContainer->getParameter($name);
            }
        }

        throw new InvalidArgumentException(sprintf('Parameter "%s" not found', $name));
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $name): bool
    {
        try {
            $this->get($name);

            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $name, array|bool|string|int|float|UnitEnum|null $value): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $name): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function replace(array $parameters): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function add(array $parameters): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function resolveValue(mixed $value): mixed
    {
        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function escapeValue(mixed $value): mixed
    {
        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function unescapeValue(mixed $value): mixed
    {
        return $value;
    }
}
