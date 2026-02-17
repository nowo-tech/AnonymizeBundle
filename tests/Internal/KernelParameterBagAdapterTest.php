<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Internal;

use InvalidArgumentException;
use Nowo\AnonymizeBundle\Internal\KernelParameterBagAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Test case for KernelParameterBagAdapter.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class KernelParameterBagAdapterTest extends TestCase
{
    /**
     * Test get() throws when container has no kernel.
     */
    public function testGetThrowsWhenNoKernel(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('kernel')->willReturn(false);

        $adapter = new KernelParameterBagAdapter($container);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "foo" not found');
        $adapter->get('foo');
    }

    /**
     * Test get() returns value when kernel container has getParameterBag().
     */
    public function testGetReturnsValueViaGetParameterBag(): void
    {
        $paramBag = $this->createMock(ParameterBagInterface::class);
        $paramBag->method('get')->with('nowo_anonymize.locale')->willReturn('en_US');

        $kernelContainer = $this->createMock(\Symfony\Component\DependencyInjection\Container::class);
        $kernelContainer->method('getParameterBag')->willReturn($paramBag);

        $kernel = new class {
            public $container;
        };
        $kernel->container = $kernelContainer;

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('kernel')->willReturn(true);
        $container->method('get')->with('kernel')->willReturn($kernel);

        $adapter = new KernelParameterBagAdapter($container);
        $value   = $adapter->get('nowo_anonymize.locale');

        $this->assertSame('en_US', $value);
    }

    /**
     * Test get() throws when kernel has no container property.
     */
    public function testGetThrowsWhenKernelHasNoContainerProperty(): void
    {
        $kernel = new stdClass();

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('kernel')->willReturn(true);
        $container->method('get')->with('kernel')->willReturn($kernel);

        $adapter = new KernelParameterBagAdapter($container);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "foo" not found');
        $adapter->get('foo');
    }

    /**
     * Test get() throws when kernel container is not a Container instance.
     */
    public function testGetThrowsWhenKernelContainerIsNotContainerInstance(): void
    {
        $kernel = new class {
            public $container;
        };
        $kernel->container = new stdClass();

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('kernel')->willReturn(true);
        $container->method('get')->with('kernel')->willReturn($kernel);

        $adapter = new KernelParameterBagAdapter($container);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "foo" not found');
        $adapter->get('foo');
    }

    /**
     * Test has() returns true when get() succeeds.
     */
    public function testHasReturnsTrueWhenParameterExists(): void
    {
        $paramBag = $this->createMock(ParameterBagInterface::class);
        $paramBag->method('get')->with('test.param')->willReturn('value');

        $kernelContainer = $this->createMock(\Symfony\Component\DependencyInjection\Container::class);
        $kernelContainer->method('getParameterBag')->willReturn($paramBag);

        $kernel = new class {
            public $container;
        };
        $kernel->container = $kernelContainer;

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('kernel')->willReturn(true);
        $container->method('get')->with('kernel')->willReturn($kernel);

        $adapter = new KernelParameterBagAdapter($container);

        $this->assertTrue($adapter->has('test.param'));
    }

    /**
     * Test has() returns false when get() throws.
     */
    public function testHasReturnsFalseWhenParameterNotFound(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('kernel')->willReturn(false);

        $adapter = new KernelParameterBagAdapter($container);

        $this->assertFalse($adapter->has('missing.param'));
    }

    /**
     * Test no-op methods do not throw.
     */
    public function testNoOpMethods(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $adapter   = new KernelParameterBagAdapter($container);

        $adapter->set('x', 'y');
        $adapter->remove('x');
        $this->assertSame([], $adapter->all());
        $adapter->replace([]);
        $adapter->add([]);
        $adapter->clear();
        $adapter->resolve();
        $this->assertSame(42, $adapter->resolveValue(42));
        $this->assertSame('a', $adapter->escapeValue('a'));
        $this->assertSame('b', $adapter->unescapeValue('b'));
    }
}
