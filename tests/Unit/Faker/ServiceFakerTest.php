<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Faker;

use Nowo\AnonymizeBundle\Faker\FakerInterface;
use Nowo\AnonymizeBundle\Faker\ServiceFaker;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use stdClass;

use const E_USER_DEPRECATED;

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
        $result       = $serviceFaker->generate(['option' => 'value']);

        $this->assertEquals('anonymized_value', $result);
    }

    /**
     * Test that ServiceFaker uses service with generate method.
     */
    #[IgnoreDeprecations]
    public function testGenerateWithGenerateMethod(): void
    {
        $mockService = new class {
            /** @param array<string, mixed> $options */
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
        $result       = $serviceFaker->generate();

        $this->assertEquals('generated_value', $result);
    }

    /**
     * Test that ServiceFaker uses callable service.
     */
    #[IgnoreDeprecations]
    public function testGenerateWithCallableService(): void
    {
        $callableService = static fn (array $options = []): string => 'callable_value';

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
        $result       = $serviceFaker->generate();

        $this->assertEquals('callable_value', $result);
    }

    /**
     * Test that ServiceFaker uses service with __invoke method.
     */
    #[IgnoreDeprecations]
    public function testGenerateWithInvokeMethod(): void
    {
        $invokableService = new class {
            /** @param array<string, mixed> $options */
            public function __invoke(array $options = []): string
            {
                return 'invoked_value';
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
            ->willReturn($invokableService);

        $serviceFaker = new ServiceFaker($container, 'test_service');
        $result       = $serviceFaker->generate();

        $this->assertEquals('invoked_value', $result);
    }

    /**
     * Test that ServiceFaker passes options to service.
     */
    public function testGeneratePassesOptions(): void
    {
        $mockFaker = $this->createMock(FakerInterface::class);
        $mockFaker->expects($this->once())
            ->method('generate')
            ->with(['key' => 'value', 'original_value' => 'test'])
            ->willReturn('result');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturn($mockFaker);

        $serviceFaker = new ServiceFaker($container, 'test_service');
        $result       = $serviceFaker->generate(['key' => 'value', 'original_value' => 'test']);

        $this->assertEquals('result', $result);
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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Service "non_existent_service" not found.');

        $serviceFaker->generate();
    }

    /**
     * Test that ServiceFaker throws exception when service doesn't implement required interface.
     */
    public function testGenerateThrowsExceptionWhenServiceInvalid(): void
    {
        $invalidService = new stdClass();

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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Service "invalid_service" must implement FakerInterface, have a generate() method, or be callable.');

        $serviceFaker->generate();
    }

    /**
     * Test that ServiceFaker throws when the container returns a non-object value.
     */
    public function testGenerateThrowsExceptionWhenServiceIsNotAnObject(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('scalar_service')
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with('scalar_service')
            ->willReturn('not-an-object');

        $serviceFaker = new ServiceFaker($container, 'scalar_service');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Service "scalar_service" must be an object.');

        $serviceFaker->generate();
    }

    #[IgnoreDeprecations]
    public function testDuckTypedServiceTriggersADeprecation(): void
    {
        $service = new class {
            /**
             * @param array<string, mixed> $options
             */
            public function generate(array $options = []): string
            {
                return 'duck';
            }
        };
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($service);

        error_clear_last();
        $result = (new ServiceFaker($container, 'app.duck'))->generate();
        $last   = error_get_last();

        $this->assertSame('duck', $result);
        $this->assertIsArray($last);
        $this->assertSame(E_USER_DEPRECATED, $last['type']);
        $this->assertStringContainsString('Custom faker service "app.duck" does not implement', $last['message']);
    }
}
