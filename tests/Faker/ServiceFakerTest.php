<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\FakerInterface;
use Nowo\AnonymizeBundle\Faker\ServiceFaker;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Test case for ServiceFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class ServiceFakerTest extends TestCase
{
    /**
     * Test that ServiceFaker uses service implementing FakerInterface.
     */
    public function testGenerateWithFakerInterface(): void
    {
        $mockFaker = $this->createMock(FakerInterface::class);
        $mockFaker->expects($this->once())
            ->method('generate')
            ->with(['option' => 'value'])
            ->willReturn('anonymized_value');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('test_service')
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with('test_service')
            ->willReturn($mockFaker);

        $serviceFaker = new ServiceFaker($container, 'test_service');
        $result = $serviceFaker->generate(['option' => 'value']);

        $this->assertEquals('anonymized_value', $result);
    }

    /**
     * Test that ServiceFaker uses service with generate method.
     */
    public function testGenerateWithGenerateMethod(): void
    {
        $mockService = new class {
            public function generate(array $options = []): string
            {
                return 'generated_value';
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('test_service')
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with('test_service')
            ->willReturn($mockService);

        $serviceFaker = new ServiceFaker($container, 'test_service');
        $result = $serviceFaker->generate();

        $this->assertEquals('generated_value', $result);
    }

    /**
     * Test that ServiceFaker uses callable service.
     */
    public function testGenerateWithCallableService(): void
    {
        $callableService = fn(array $options = []) => 'callable_value';

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('test_service')
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with('test_service')
            ->willReturn($callableService);

        $serviceFaker = new ServiceFaker($container, 'test_service');
        $result = $serviceFaker->generate();

        $this->assertEquals('callable_value', $result);
    }

    /**
     * Test that ServiceFaker throws exception when service not found.
     */
    public function testGenerateThrowsExceptionWhenServiceNotFound(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('non_existent_service')
            ->willReturn(false);

        $serviceFaker = new ServiceFaker($container, 'non_existent_service');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Service "non_existent_service" not found.');

        $serviceFaker->generate();
    }

    /**
     * Test that ServiceFaker throws exception when service doesn't implement required interface.
     */
    public function testGenerateThrowsExceptionWhenServiceInvalid(): void
    {
        $invalidService = new \stdClass();

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('invalid_service')
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with('invalid_service')
            ->willReturn($invalidService);

        $serviceFaker = new ServiceFaker($container, 'invalid_service');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Service "invalid_service" must implement FakerInterface, have a generate() method, or be callable.');

        $serviceFaker->generate();
    }
}
