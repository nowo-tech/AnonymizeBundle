<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Metadata;

use Nowo\AnonymizeBundle\Metadata\ConfigAnonymizeMetadataProvider;
use Nowo\AnonymizeBundle\Tests\Integration\Ddd\Domain\ContactDetails;
use Nowo\AnonymizeBundle\Tests\Integration\Ddd\Domain\Patient;
use PHPUnit\Framework\TestCase;

final class ConfigAnonymizeMetadataProviderTest extends TestCase
{
    public function testBuildsValueObjectsFromConfiguration(): void
    {
        $provider = new ConfigAnonymizeMetadataProvider([
            '\\' . Patient::class => [
                'connection'        => 'tenant',
                'include_patterns'  => ['status' => 'active'],
                'exclude_patterns'  => [['email' => '%@keep.test']],
                'anonymize_service' => null,
                'truncate'          => false,
                'truncate_order'    => 3,
                'mark_anonymized'   => true,
                'properties'        => [
                    'email'         => ['type' => 'email', 'weight' => 1, 'include_patterns' => [], 'exclude_patterns' => ['status' => 'keep'], 'service' => null, 'options' => ['domain' => 'example.test']],
                    'contact.phone' => ['type' => 'service', 'service' => 'app.phone_faker'],
                ],
            ],
            ContactDetails::class => [],
        ]);

        $entity = $provider->getEntity(Patient::class);
        self::assertNotNull($entity);
        self::assertSame('tenant', $entity->connection);
        self::assertSame(['status' => 'active'], $entity->includePatterns);
        self::assertSame([['email' => '%@keep.test']], $entity->excludePatterns);
        self::assertNull($entity->anonymizeService);
        self::assertFalse($entity->truncate);
        self::assertSame(3, $entity->truncate_order);
        self::assertTrue($provider->marksAnonymized(Patient::class));

        $properties = $provider->getProperties(Patient::class);
        self::assertSame(['email', 'contact.phone'], array_keys($properties));
        self::assertSame('email', $properties['email']->type);
        self::assertSame(1, $properties['email']->weight);
        self::assertSame(['status' => 'keep'], $properties['email']->excludePatterns);
        self::assertSame(['domain' => 'example.test'], $properties['email']->options);
        self::assertSame('app.phone_faker', $properties['contact.phone']->service);
        self::assertNull($properties['contact.phone']->weight);
        self::assertSame([], $properties['contact.phone']->options);

        $minimal = $provider->getEntity(ContactDetails::class);
        self::assertNotNull($minimal);
        self::assertNull($minimal->connection);
        self::assertSame([], $minimal->includePatterns);
        self::assertFalse($provider->marksAnonymized(ContactDetails::class));
        self::assertSame([], $provider->getProperties(ContactDetails::class));
    }

    public function testUnknownClassesAreNotSelected(): void
    {
        $provider = new ConfigAnonymizeMetadataProvider();

        self::assertNull($provider->getEntity(Patient::class));
        self::assertSame([], $provider->getProperties(Patient::class));
        self::assertFalse($provider->marksAnonymized(Patient::class));
    }
}
