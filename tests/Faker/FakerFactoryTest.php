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
     * Test that FakerFactory creates service faker with container.
     */
    public function testCreateServiceFaker(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new FakerFactory('en_US', $container);
        $faker = $factory->create('service', 'test_service');

        $this->assertInstanceOf(FakerInterface::class, $faker);
    }
}
