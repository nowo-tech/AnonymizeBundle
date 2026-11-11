<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Helper;

use Doctrine\ORM\Mapping\ClassMetadata;
use InvalidArgumentException;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Helper\AnonymizePropertyDiscovery;
use Nowo\AnonymizeBundle\Tests\Integration\Ddd\Domain\ContactDetails;
use Nowo\AnonymizeBundle\Tests\Integration\Ddd\Domain\Patient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

use function sprintf;

/**
 * Since 1.5: fields selected by configuration (no attributes) and embeddables known only from Doctrine metadata.
 */
final class AnonymizePropertyDiscoveryConfiguredTest extends TestCase
{
    public function testConfiguredFieldsResolveTopLevelInheritedAndEmbeddedProperties(): void
    {
        $result = AnonymizePropertyDiscovery::discover(new ReflectionClass(DiscoveryConfiguredChild::class), null, [
            'secret'        => new AnonymizeProperty('hash', weight: 2),
            'email'         => new AnonymizeProperty('email'),
            'contact.phone' => new AnonymizeProperty('phone', weight: 1),
        ]);

        $byField = array_column($result, null, 'fieldName');
        self::assertSame(['contact.phone', 'secret', 'email', 'nickname'], array_keys($byField));
        self::assertSame(DiscoveryConfiguredParent::class, $byField['secret']['property']->getDeclaringClass()->getName());
        self::assertSame(ContactDetails::class, $byField['contact.phone']['property']->getDeclaringClass()->getName());
        self::assertSame('phone', $byField['contact.phone']['attribute']->type);
    }

    public function testAnAttributeOnTheSameFieldWins(): void
    {
        $result = AnonymizePropertyDiscovery::discover(new ReflectionClass(DiscoveryConfiguredChild::class), null, [
            'nickname' => new AnonymizeProperty('email'),
        ]);

        self::assertCount(1, $result);
        self::assertSame('name', $result[0]['attribute']->type);
    }

    public function testEmbedPathUsesTheDoctrineMetadata(): void
    {
        $metadata = new ClassMetadata(Patient::class);
        $this->setEmbeddedClasses($metadata, ['contact' => ['class' => ContactDetails::class]]);

        $result = AnonymizePropertyDiscovery::discover(new ReflectionClass(Patient::class), $metadata, [
            'contact.city' => new AnonymizeProperty('city'),
        ]);

        self::assertSame(ContactDetails::class, $result[0]['property']->getDeclaringClass()->getName());
    }

    public function testAttributesOnAnXmlMappedEmbeddableAreDiscoveredThroughMetadata(): void
    {
        $metadata = new ClassMetadata(DiscoveryXmlEmbedHost::class);
        $metadata->mapEmbedded(['fieldName' => 'bank', 'class' => AnonymizePropertyDiscoveryTestBankEmbed::class]);

        $withMetadata    = AnonymizePropertyDiscovery::discover(new ReflectionClass(DiscoveryXmlEmbedHost::class), $metadata);
        $withoutMetadata = AnonymizePropertyDiscovery::discover(new ReflectionClass(DiscoveryXmlEmbedHost::class));

        self::assertSame(['bank.iban'], array_column($withMetadata, 'fieldName'));
        self::assertSame([], $withoutMetadata);
    }

    public function testUnusableEmbeddedMetadataIsIgnored(): void
    {
        $metadata = new ClassMetadata(DiscoveryXmlEmbedHost::class);
        $this->setEmbeddedClasses($metadata, ['bank' => 'not a mapping']);

        self::assertSame([], AnonymizePropertyDiscovery::discover(new ReflectionClass(DiscoveryXmlEmbedHost::class), $metadata));

        $this->setEmbeddedClasses($metadata, ['bank' => ['class' => '']]);
        self::assertSame([], AnonymizePropertyDiscovery::discover(new ReflectionClass(DiscoveryXmlEmbedHost::class), $metadata));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unknownFields(): iterable
    {
        yield 'unknown field' => ['missing'];
        yield 'unknown embed host' => ['missing.phone'];
        yield 'scalar embed host' => ['email.domain'];
        yield 'unknown embedded field' => ['contact.fax'];
    }

    #[DataProvider('unknownFields')]
    public function testAConfiguredFieldThatMatchesNothingFails(string $field): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The anonymized field "%s" configured for "%s" matches no property', $field, DiscoveryConfiguredChild::class));

        AnonymizePropertyDiscovery::discover(new ReflectionClass(DiscoveryConfiguredChild::class), null, [$field => new AnonymizeProperty('name')]);
    }

    /**
     * ORM 2 stores embedded mappings as arrays, ORM 3 as objects: set the raw value to cover both.
     *
     * @param ClassMetadata<object> $metadata
     * @param array<string, mixed> $embedded
     */
    private function setEmbeddedClasses(ClassMetadata $metadata, array $embedded): void
    {
        (new ReflectionProperty(ClassMetadata::class, 'embeddedClasses'))->setValue($metadata, $embedded);
    }
}

/**
 * @internal
 */
class DiscoveryConfiguredParent
{
    private string $secret = 's';

    public function secret(): string
    {
        return $this->secret;
    }
}

/**
 * @internal
 */
final class DiscoveryConfiguredChild extends DiscoveryConfiguredParent
{
    public string $email = 'a@b.c';

    #[AnonymizeProperty(type: 'name')]
    public string $nickname = 'n';

    public ?ContactDetails $contact = null;
}

/**
 * @internal
 */
final class DiscoveryXmlEmbedHost
{
    public ?AnonymizePropertyDiscoveryTestBankEmbed $bank = null;
}
