<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Faker;

use InvalidArgumentException;
use Nowo\AnonymizeBundle\Enum\FakerType;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Faker\FakerInterface;
use Nowo\AnonymizeBundle\Faker\ServiceFaker;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

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
        $factory->create('email');

    }

    /**
     * Test that FakerFactory creates name faker.
     */
    public function testCreateNameFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('name');

    }

    /**
     * Test that FakerFactory creates surname faker.
     */
    public function testCreateSurnameFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('surname');

    }

    /**
     * Test that FakerFactory creates age faker.
     */
    public function testCreateAgeFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('age');

    }

    /**
     * Test that FakerFactory creates phone faker.
     */
    public function testCreatePhoneFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('phone');

    }

    /**
     * Test that FakerFactory creates IBAN faker.
     */
    public function testCreateIbanFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('iban');

    }

    /**
     * Test that FakerFactory creates credit card faker.
     */
    public function testCreateCreditCardFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('credit_card');

    }

    /**
     * Test that FakerFactory creates address faker.
     */
    public function testCreateAddressFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('address');

    }

    /**
     * Test that FakerFactory creates date faker.
     */
    public function testCreateDateFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('date');

    }

    /**
     * Test that FakerFactory creates username faker.
     */
    public function testCreateUsernameFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('username');

    }

    /**
     * Test that FakerFactory creates URL faker.
     */
    public function testCreateUrlFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('url');

    }

    /**
     * Test that FakerFactory creates company faker.
     */
    public function testCreateCompanyFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('company');

    }

    /**
     * Test that FakerFactory creates masking faker.
     */
    public function testCreateMaskingFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('masking');

    }

    /**
     * Test that FakerFactory creates password faker.
     */
    public function testCreatePasswordFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('password');

    }

    /**
     * Test that FakerFactory creates IP address faker.
     */
    public function testCreateIpAddressFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('ip_address');

    }

    /**
     * Test that FakerFactory creates MAC address faker.
     */
    public function testCreateMacAddressFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('mac_address');

    }

    /**
     * Test that FakerFactory creates UUID faker.
     */
    public function testCreateUuidFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('uuid');

    }

    /**
     * Test that FakerFactory creates hash faker.
     */
    public function testCreateHashFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('hash');

    }

    /**
     * Test that FakerFactory creates coordinate faker.
     */
    public function testCreateCoordinateFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('coordinate');

    }

    /**
     * Test that FakerFactory creates color faker.
     */
    public function testCreateColorFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('color');

    }

    /**
     * Test that FakerFactory creates boolean faker.
     */
    public function testCreateBooleanFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('boolean');

    }

    /**
     * Test that FakerFactory creates numeric faker.
     */
    public function testCreateNumericFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('numeric');

    }

    /**
     * Test that FakerFactory creates file faker.
     */
    public function testCreateFileFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('file');

    }

    /**
     * Test that FakerFactory creates json faker.
     */
    public function testCreateJsonFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('json');

    }

    /**
     * Test that FakerFactory creates text faker.
     */
    public function testCreateTextFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('text');

    }

    /**
     * Test that FakerFactory creates enum faker.
     */
    public function testCreateEnumFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('enum');

    }

    /**
     * Test that FakerFactory creates country faker.
     */
    public function testCreateCountryFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('country');

    }

    /**
     * Test that FakerFactory creates language faker.
     */
    public function testCreateLanguageFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('language');

    }

    /**
     * Test that FakerFactory creates faker from enum.
     */
    public function testCreateFromEnum(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create(FakerType::EMAIL);

    }

    /**
     * Test that FakerFactory throws exception for unsupported type.
     */
    public function testCreateThrowsExceptionForUnsupportedType(): void
    {
        $factory = new FakerFactory('en_US');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported faker type: invalid_type');

        $factory->create('invalid_type');
    }

    /**
     * Test that FakerFactory creates hash preserve faker.
     */
    public function testCreateHashPreserveFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('hash_preserve');

    }

    /**
     * Test that FakerFactory creates shuffle faker.
     */
    public function testCreateShuffleFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('shuffle');

    }

    /**
     * Test that FakerFactory creates constant faker.
     */
    public function testCreateConstantFaker(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create('constant');

    }

    /**
     * Test that FakerFactory creates service faker with container.
     */
    public function testCreateServiceFaker(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory   = new FakerFactory('en_US', $container);
        $factory->create('service', 'test_service');

    }

    /**
     * Test that create uses container when available.
     */
    public function testCreateUsesContainerWhenAvailable(): void
    {
        $mockFaker = $this->createMock(FakerInterface::class);
        $mockFaker->method('generate')
            ->willReturn('container_value');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->with('nowo_anonymize.faker.email')
            ->willReturn(true);
        $container->method('get')
            ->with('nowo_anonymize.faker.email')
            ->willReturn($mockFaker);

        $factory = new FakerFactory('en_US', $container);
        $faker   = $factory->create('email');

        $this->assertSame($mockFaker, $faker);
    }

    /**
     * Test that create falls back to direct instantiation when container doesn't have service.
     */
    public function testCreateFallsBackWhenContainerDoesNotHaveService(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->with('nowo_anonymize.faker.email')
            ->willReturn(false);

        $factory = new FakerFactory('en_US', $container);
        $factory->create('email');

    }

    /**
     * Test that create accepts FakerType enum.
     */
    public function testCreateAcceptsFakerTypeEnum(): void
    {
        $factory = new FakerFactory('en_US');
        $factory->create(FakerType::EMAIL);

    }

    /**
     * Test that create handles service type with service name.
     */
    public function testCreateHandlesServiceTypeWithServiceName(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory   = new FakerFactory('en_US', $container);
        $factory->create('service', 'custom_service');

    }

    /**
     * Test that create handles all faker types from container when available.
     */
    public function testCreateAllTypesFromContainer(): void
    {
        $mockFaker = $this->createMock(FakerInterface::class);
        $container = $this->createMock(ContainerInterface::class);

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
        }
    }

    /**
     * Test that create handles container exception gracefully.
     */
    public function testCreateHandlesContainerException(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->with('nowo_anonymize.faker.email')
            ->willReturn(true);

        $exception = $this->createMock(ContainerExceptionInterface::class);
        $container->method('get')
            ->with('nowo_anonymize.faker.email')
            ->willThrowException($exception);

        $factory = new FakerFactory('en_US', $container);

        // When container throws exception, it should propagate
        // The factory doesn't catch exceptions from container
        $this->expectException(ContainerExceptionInterface::class);
        $factory->create('email');
    }

    /**
     * Test that create uses different locale when provided.
     */
    public function testCreateUsesLocale(): void
    {
        $factory = new FakerFactory('es_ES');
        $factory->create('email');

    }

    /**
     * Test that create with unsupported type throws when container does not have the service.
     */
    public function testCreateUnsupportedTypeThrowsWhenContainerDoesNotHaveService(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $factory = new FakerFactory('en_US', $container);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported faker type: unsupported_xyz');

        $factory->create('unsupported_xyz');
    }

    /**
     * Test that create('service') with null service name uses empty string.
     */
    public function testCreateServiceWithNullServiceName(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory   = new FakerFactory('en_US', $container);
        $factory->create('service');

    }

    /**
     * Test that create handles service type without container.
     */
    public function testCreateServiceTypeWithoutContainer(): void
    {
        // ServiceFaker requires a container, so we need to provide one
        $container = $this->createMock(ContainerInterface::class);
        $factory   = new FakerFactory('en_US', $container);
        $factory->create('service', 'test_service');

    }
}
