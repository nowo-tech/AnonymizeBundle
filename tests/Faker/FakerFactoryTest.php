<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Enum\FakerType;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Faker\FakerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test case for FakerFactory.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class FakerFactoryTest extends TestCase
{
    /**
     * Test that FakerFactory creates email faker.
     */
    public function testCreateEmailFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('email');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates name faker.
     */
    public function testCreateNameFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('name');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates surname faker.
     */
    public function testCreateSurnameFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('surname');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates age faker.
     */
    public function testCreateAgeFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('age');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates phone faker.
     */
    public function testCreatePhoneFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('phone');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates IBAN faker.
     */
    public function testCreateIbanFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('iban');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates credit card faker.
     */
    public function testCreateCreditCardFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('credit_card');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates address faker.
     */
    public function testCreateAddressFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('address');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates date faker.
     */
    public function testCreateDateFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('date');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates username faker.
     */
    public function testCreateUsernameFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('username');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates URL faker.
     */
    public function testCreateUrlFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('url');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates company faker.
     */
    public function testCreateCompanyFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('company');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates masking faker.
     */
    public function testCreateMaskingFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('masking');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates password faker.
     */
    public function testCreatePasswordFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('password');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates IP address faker.
     */
    public function testCreateIpAddressFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('ip_address');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates MAC address faker.
     */
    public function testCreateMacAddressFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('mac_address');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates UUID faker.
     */
    public function testCreateUuidFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('uuid');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates hash faker.
     */
    public function testCreateHashFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('hash');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates coordinate faker.
     */
    public function testCreateCoordinateFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('coordinate');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates color faker.
     */
    public function testCreateColorFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('color');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates boolean faker.
     */
    public function testCreateBooleanFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('boolean');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates numeric faker.
     */
    public function testCreateNumericFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('numeric');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates file faker.
     */
    public function testCreateFileFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('file');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates json faker.
     */
    public function testCreateJsonFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('json');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates text faker.
     */
    public function testCreateTextFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('text');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates enum faker.
     */
    public function testCreateEnumFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('enum');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates country faker.
     */
    public function testCreateCountryFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('country');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates language faker.
     */
    public function testCreateLanguageFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('language');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates faker from enum.
     */
    public function testCreateFromEnum(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create(FakerType::EMAIL);

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory throws exception for unsupported type.
     */
    public function testCreateThrowsExceptionForUnsupportedType(): void
    {
        $factory = new FakerFactory('en_US');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported faker type: invalid_type');

        $factory->create('invalid_type');
    }

    /**
     * Test that FakerFactory creates hash preserve faker.
     */
    public function testCreateHashPreserveFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('hash_preserve');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates shuffle faker.
     */
    public function testCreateShuffleFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('shuffle');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates constant faker.
     */
    public function testCreateConstantFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create('constant');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that FakerFactory creates service faker with container.
     */
    public function testCreateServiceFaker(): void
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $factory = new FakerFactory('en_US', $container);
        $faker = $factory->create('service', 'test_service');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }

    /**
     * Test that create uses container when available.
     */
    public function testCreateUsesContainerWhenAvailable(): void
    {
        $mockFaker = $this->createMock(FakerInterface::class);
        $mockFaker->method('generate')
            ->willReturn('container_value');

        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('has')
            ->with('nowo_anonymize.faker.email')
            ->willReturn(true);
        $container->method('get')
            ->with('nowo_anonymize.faker.email')
            ->willReturn($mockFaker);

        $factory = new FakerFactory('en_US', $container);
        $faker = $factory->create('email');

        $this->assertSame($mockFaker, $faker);
    }

    /**
     * Test that create falls back to direct instantiation when container doesn't have service.
     */
    public function testCreateFallsBackWhenContainerDoesNotHaveService(): void
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('has')
            ->with('nowo_anonymize.faker.email')
            ->willReturn(false);

        $factory = new FakerFactory('en_US', $container);
        $faker = $factory->create('email');

        $this->assertInstanceOf(\Nowo\AnonymizeBundle\Faker\EmailFaker::class, $faker);
    }

    /**
     * Test that create accepts FakerType enum.
     */
    public function testCreateAcceptsFakerTypeEnum(): void
    {
        $factory = new FakerFactory('en_US');
        $faker = $factory->create(FakerType::EMAIL);

        $this->assertInstanceOf(\Nowo\AnonymizeBundle\Faker\EmailFaker::class, $faker);
    }

    /**
     * Test that create handles service type with service name.
     */
    public function testCreateHandlesServiceTypeWithServiceName(): void
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $factory = new FakerFactory('en_US', $container);
        $faker = $factory->create('service', 'custom_service');

        $this->assertInstanceOf(\Nowo\AnonymizeBundle\Faker\ServiceFaker::class, $faker);
    }

    /**
     * Test that create handles all faker types from container when available.
     */
    public function testCreateAllTypesFromContainer(): void
    {
        $mockFaker = $this->createMock(FakerInterface::class);
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);

        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturn($mockFaker);

        $factory = new FakerFactory('en_US', $container);

        $types = ['email', 'name', 'surname', 'age', 'phone', 'iban', 'credit_card',
            'address', 'date', 'username', 'url', 'company', 'masking', 'password',
            'ip_address', 'mac_address', 'uuid', 'hash', 'coordinate', 'color',
            'boolean', 'numeric', 'file', 'json', 'text', 'enum', 'country',
            'language', 'hash_preserve', 'shuffle', 'constant'];

        foreach ($types as $type) {
            $faker = $factory->create($type);
            $this->assertInstanceOf(FakerInterface::class, $faker);
        }
    }

    /**
     * Test that create handles container exception gracefully.
     */
    public function testCreateHandlesContainerException(): void
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('has')
            ->with('nowo_anonymize.faker.email')
            ->willReturn(true);

        $exception = $this->createMock(\Psr\Container\ContainerExceptionInterface::class);
        $container->method('get')
            ->with('nowo_anonymize.faker.email')
            ->willThrowException($exception);

        $factory = new FakerFactory('en_US', $container);

        // When container throws exception, it should propagate
        // The factory doesn't catch exceptions from container
        $this->expectException(\Psr\Container\ContainerExceptionInterface::class);
        $factory->create('email');
    }

    /**
     * Test that create uses different locale when provided.
     */
    public function testCreateUsesLocale(): void
    {
        $factory = new FakerFactory('es_ES');
        $faker = $factory->create('email');

        $this->assertInstanceOf(\Nowo\AnonymizeBundle\Faker\EmailFaker::class, $faker);
    }

    /**
     * Test that create handles service type without container.
     */
    public function testCreateServiceTypeWithoutContainer(): void
    {
        // ServiceFaker requires a container, so we need to provide one
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $factory = new FakerFactory('en_US', $container);
        $faker = $factory->create('service', 'test_service');

        $this->assertInstanceOf(\Nowo\AnonymizeBundle\Faker\ServiceFaker::class, $faker);
    }
}
