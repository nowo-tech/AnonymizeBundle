<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Helper;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Helper\AnonymizePropertyDiscovery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AnonymizePropertyDiscoveryTest extends TestCase
{
    public function testDiscoversTopLevelProperties(): void
    {
        $entity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1)]
            public string $email = 'a@b.com';

            public string $ignored = 'x';
        };

        $result = AnonymizePropertyDiscovery::discover(new ReflectionClass($entity));

        $this->assertCount(1, $result);
        $this->assertSame('email', $result[0]['fieldName']);
        $this->assertSame('email', $result[0]['property']->getName());
        $this->assertSame(1, $result[0]['weight']);
    }

    public function testDiscoversEmbeddedPropertiesWithDoctrinePath(): void
    {
        $entity = new class {
            #[AnonymizeProperty(type: 'name', weight: 1)]
            public string $name = 'Ada';

            #[ORM\Embedded(class: AnonymizePropertyDiscoveryTestPhoneEmbed::class, columnPrefix: 'phone_')]
            public AnonymizePropertyDiscoveryTestPhoneEmbed $phoneNumber;
        };
        $entity->phoneNumber = new AnonymizePropertyDiscoveryTestPhoneEmbed();

        $result = AnonymizePropertyDiscovery::discover(new ReflectionClass($entity));

        $fieldNames = array_map(static fn (array $item): string => $item['fieldName'], $result);
        $this->assertContains('name', $fieldNames);
        $this->assertContains('phoneNumber.number', $fieldNames);
        $this->assertContains('phoneNumber.verificationCode', $fieldNames);
        $this->assertNotContains('phoneNumber', $fieldNames);

        $byField = [];
        foreach ($result as $item) {
            $byField[$item['fieldName']] = $item;
        }
        $this->assertSame('phone', $byField['phoneNumber.number']['attribute']->type);
        $this->assertSame(10, $byField['phoneNumber.number']['weight']);
        $this->assertSame('null', $byField['phoneNumber.verificationCode']['attribute']->type);
    }

    public function testResolvesEmbeddedClassFromPropertyTypeWhenAttributeClassIsNull(): void
    {
        $entity = new class {
            #[ORM\Embedded(columnPrefix: 'bank_')]
            public AnonymizePropertyDiscoveryTestBankEmbed $bankAccount;
        };
        $entity->bankAccount = new AnonymizePropertyDiscoveryTestBankEmbed();

        $result = AnonymizePropertyDiscovery::discover(new ReflectionClass($entity));

        $this->assertCount(1, $result);
        $this->assertSame('bankAccount.iban', $result[0]['fieldName']);
        $this->assertSame('iban', $result[0]['attribute']->type);
    }

    public function testIgnoresAnonymizePropertyOnEmbeddedHostPropertyItself(): void
    {
        $entity = new class {
            #[AnonymizeProperty(type: 'text', weight: 1)]
            #[ORM\Embedded(class: AnonymizePropertyDiscoveryTestPhoneEmbed::class)]
            public AnonymizePropertyDiscoveryTestPhoneEmbed $phoneNumber;
        };
        $entity->phoneNumber = new AnonymizePropertyDiscoveryTestPhoneEmbed();

        $result     = AnonymizePropertyDiscovery::discover(new ReflectionClass($entity));
        $fieldNames = array_map(static fn (array $item): string => $item['fieldName'], $result);

        $this->assertNotContains('phoneNumber', $fieldNames);
        $this->assertContains('phoneNumber.number', $fieldNames);
    }

    public function testSortsByWeightIncludingEmbedded(): void
    {
        $entity = new class {
            #[AnonymizeProperty(type: 'name', weight: 5)]
            public string $name = 'x';

            #[ORM\Embedded(class: AnonymizePropertyDiscoveryTestPhoneEmbed::class)]
            public AnonymizePropertyDiscoveryTestPhoneEmbed $phoneNumber;
        };
        $entity->phoneNumber = new AnonymizePropertyDiscoveryTestPhoneEmbed();

        $result     = AnonymizePropertyDiscovery::discover(new ReflectionClass($entity));
        $fieldNames = array_map(static fn (array $item): string => $item['fieldName'], $result);

        $this->assertSame('name', $fieldNames[0]);
        $this->assertSame('phoneNumber.number', $fieldNames[1]);
        $this->assertSame('phoneNumber.verificationCode', $fieldNames[2]);
    }
}

/**
 * @internal
 */
final class AnonymizePropertyDiscoveryTestPhoneEmbed
{
    #[AnonymizeProperty(type: 'phone', weight: 10)]
    public ?string $number = null;

    #[AnonymizeProperty(type: 'null', weight: 20)]
    public ?string $verificationCode = null;

    public bool $verified = false;
}

/**
 * @internal
 */
final class AnonymizePropertyDiscoveryTestBankEmbed
{
    #[AnonymizeProperty(type: 'iban', weight: 1)]
    public ?string $iban = null;
}
