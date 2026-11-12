<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use LogicException;
use Nowo\AnonymizeBundle\Event\AfterAnonymizeEvent;
use Nowo\AnonymizeBundle\Event\AfterEntityAnonymizeEvent;
use Nowo\AnonymizeBundle\Event\AnonymizePropertyEvent;
use Nowo\AnonymizeBundle\Event\BeforeAnonymizeEvent;
use Nowo\AnonymizeBundle\Event\BeforeEntityAnonymizeEvent;
use Nowo\AnonymizeBundle\Metadata\AnonymizeTarget;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * 1.6: the events built with create() carry scalars and an AnonymizeTarget only (no deprecation on that path); the
 * deprecated Doctrine getters of such an event fail clearly, and the deprecated constructors fill the new accessors.
 */
final class FrameworkFreeEventsTest extends TestCase
{
    public function testRunEventsCarryScalars(): void
    {
        $before = BeforeAnonymizeEvent::create(['App\Domain\Patient'], true, 'default');
        self::assertSame(['App\Domain\Patient'], $before->getEntityClasses());
        self::assertTrue($before->isDryRun());
        self::assertSame('default', $before->getEntityManagerName());
        $before->setEntityClasses([]);
        self::assertSame([], $before->getEntityClasses());
        self::assertNull(BeforeAnonymizeEvent::create([])->getEntityManagerName());
        self::assertFalse(BeforeAnonymizeEvent::create([])->isDryRun());

        $after = AfterAnonymizeEvent::create(['App\Domain\Patient'], 7, 4, true, 'reporting');
        self::assertSame(['App\Domain\Patient'], $after->getEntityClasses());
        self::assertSame(7, $after->getTotalProcessed());
        self::assertSame(4, $after->getTotalUpdated());
        self::assertTrue($after->isDryRun());
        self::assertSame('reporting', $after->getEntityManagerName());
        self::assertNull(AfterAnonymizeEvent::create([], 0, 0)->getEntityManagerName());
    }

    public function testEntityEventsCarryTheTarget(): void
    {
        $target = $this->target();

        $before = BeforeEntityAnonymizeEvent::create($target, 12, true);
        self::assertSame($target, $before->getTarget());
        self::assertSame('App\Domain\Patient', $before->getEntityClass());
        self::assertSame('ddd_patient', $before->getTableName());
        self::assertSame(12, $before->getTotalRecords());
        self::assertTrue($before->isDryRun());

        $after = AfterEntityAnonymizeEvent::create($target, 12, 5, ['email' => 5]);
        self::assertSame($target, $after->getTarget());
        self::assertSame('App\Domain\Patient', $after->getEntityClass());
        self::assertSame('ddd_patient', $after->getTableName());
        self::assertSame(12, $after->getProcessed());
        self::assertSame(5, $after->getUpdated());
        self::assertSame(['email' => 5], $after->getPropertyStats());
        self::assertFalse($after->isDryRun());
    }

    public function testPropertyEventCarriesTheTargetAndNames(): void
    {
        $target = $this->target();
        $event  = AnonymizePropertyEvent::create($target, 'phone', 'contact_phone', '+34 600', 'anon', ['id' => 'p-1'], true, 'contact.phone');

        self::assertSame($target, $event->getTarget());
        self::assertSame('App\Domain\Patient', $event->getEntityClass());
        self::assertSame('ddd_patient', $event->getTableName());
        self::assertSame('phone', $event->getPropertyName());
        self::assertSame('contact.phone', $event->getFieldName());
        self::assertSame('contact_phone', $event->getColumnName());
        self::assertSame('+34 600', $event->getOriginalValue());
        self::assertSame('anon', $event->getAnonymizedValue());
        self::assertSame(['id' => 'p-1'], $event->getRecord());
        self::assertTrue($event->isDryRun());
        self::assertFalse($event->shouldSkipAnonymization());
        $event->setAnonymizedValue('other');
        $event->setSkipAnonymization(true);
        self::assertSame('other', $event->getAnonymizedValue());
        self::assertTrue($event->shouldSkipAnonymization());

        $plain = AnonymizePropertyEvent::create($target, 'email', 'email', 'a@b.test', 'x', []);
        self::assertSame('email', $plain->getFieldName(), 'the field name defaults to the property name');
        self::assertFalse($plain->isDryRun());
    }

    #[IgnoreDeprecations]
    public function testDeprecatedGettersOfAFrameworkFreeEventFailClearly(): void
    {
        $target = $this->target();
        $calls  = [
            'BeforeAnonymizeEvent::getEntityManager'       => static fn (): object => BeforeAnonymizeEvent::create([])->getEntityManager(),
            'AfterAnonymizeEvent::getEntityManager'        => static fn (): object => AfterAnonymizeEvent::create([], 0, 0)->getEntityManager(),
            'BeforeEntityAnonymizeEvent::getEntityManager' => static fn (): object => BeforeEntityAnonymizeEvent::create($target, 0)->getEntityManager(),
            'BeforeEntityAnonymizeEvent::getMetadata'      => static fn (): object => BeforeEntityAnonymizeEvent::create($target, 0)->getMetadata(),
            'BeforeEntityAnonymizeEvent::getReflection'    => static fn (): object => BeforeEntityAnonymizeEvent::create($target, 0)->getReflection(),
            'AfterEntityAnonymizeEvent::getEntityManager'  => static fn (): object => AfterEntityAnonymizeEvent::create($target, 0, 0, [])->getEntityManager(),
            'AfterEntityAnonymizeEvent::getMetadata'       => static fn (): object => AfterEntityAnonymizeEvent::create($target, 0, 0, [])->getMetadata(),
            'AfterEntityAnonymizeEvent::getReflection'     => static fn (): object => AfterEntityAnonymizeEvent::create($target, 0, 0, [])->getReflection(),
            'AnonymizePropertyEvent::getEntityManager'     => static fn (): object => AnonymizePropertyEvent::create($target, 'email', 'email', null, null, [])->getEntityManager(),
            'AnonymizePropertyEvent::getMetadata'          => static fn (): object => AnonymizePropertyEvent::create($target, 'email', 'email', null, null, [])->getMetadata(),
            'AnonymizePropertyEvent::getProperty'          => static fn (): object => AnonymizePropertyEvent::create($target, 'email', 'email', null, null, [])->getProperty(),
        ];

        foreach ($calls as $name => $call) {
            try {
                $call();
                self::fail($name . ' should fail');
            } catch (LogicException $e) {
                self::assertStringContainsString('built with create()', $e->getMessage(), $name);
            }
        }
    }

    #[IgnoreDeprecations]
    public function testDeprecatedConstructorsFillTheNewAccessors(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getName')->willReturn('App\Domain\Patient');
        $metadata->method('getTableName')->willReturn('ddd_patient');
        $metadata->method('getIdentifierColumnNames')->willReturn(['id']);
        $metadata->method('getFieldNames')->willReturn(['id', 'email']);
        $metadata->method('getColumnName')->willReturnCallback(static fn (string $field): string => $field === 'email' ? 'email_address' : $field);
        $reflection = new ReflectionClass(self::class);
        $property   = new ReflectionProperty(AnonymizeTarget::class, 'tableName');
        $expected   = new AnonymizeTarget('App\Domain\Patient', 'ddd_patient', ['id'], ['id' => 'id', 'email' => 'email_address']);

        $before = new BeforeEntityAnonymizeEvent($em, $metadata, $reflection, 3, true);
        self::assertEquals($expected, $before->getTarget());
        self::assertSame('ddd_patient', $before->getTableName());
        self::assertSame($reflection, $before->getReflection());

        $after = new AfterEntityAnonymizeEvent($em, $metadata, $reflection, 3, 2, [], true);
        self::assertEquals($expected, $after->getTarget());
        self::assertSame('ddd_patient', $after->getTableName());

        $propertyEvent = new AnonymizePropertyEvent($em, $metadata, $property, 'table_name', 'a', 'b', [], false);
        self::assertEquals($expected, $propertyEvent->getTarget());
        self::assertSame('App\Domain\Patient', $propertyEvent->getEntityClass());
        self::assertSame('ddd_patient', $propertyEvent->getTableName());
        self::assertSame('tableName', $propertyEvent->getFieldName());

        self::assertNull((new BeforeAnonymizeEvent($em, [], false))->getEntityManagerName());
        self::assertNull((new AfterAnonymizeEvent($em, [], 0, 0, false))->getEntityManagerName());
    }

    private function target(): AnonymizeTarget
    {
        return new AnonymizeTarget('App\Domain\Patient', 'ddd_patient', ['id'], ['id' => 'id', 'email' => 'email', 'contact.phone' => 'contact_phone']);
    }
}
