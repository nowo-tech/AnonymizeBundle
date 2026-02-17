<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nowo\AnonymizeBundle\Event\AnonymizePropertyEvent;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Test case for AnonymizePropertyEvent.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizePropertyEventTest extends TestCase
{
    private EntityManagerInterface $em;
    private ClassMetadata $metadata;

    protected function setUp(): void
    {
        $this->em       = $this->createMock(EntityManagerInterface::class);
        $this->metadata = $this->createMock(ClassMetadata::class);
    }

    private function createTestProperty(): ReflectionProperty
    {
        $testClass = new class {
            public string $testProperty = 'test';
        };

        return new ReflectionProperty($testClass, 'testProperty');
    }

    public function testGetEntityManager(): void
    {
        $property = $this->createTestProperty();
        $event    = new AnonymizePropertyEvent(
            $this->em,
            $this->metadata,
            $property,
            'email',
            'original@example.com',
            'anonymized@example.com',
            ['id' => 1, 'email' => 'original@example.com'],
            false,
        );

        $this->assertSame($this->em, $event->getEntityManager());
    }

    public function testGetMetadata(): void
    {
        $property = $this->createTestProperty();
        $event    = new AnonymizePropertyEvent(
            $this->em,
            $this->metadata,
            $property,
            'email',
            'original@example.com',
            'anonymized@example.com',
            ['id' => 1],
            false,
        );

        $this->assertSame($this->metadata, $event->getMetadata());
    }

    public function testGetProperty(): void
    {
        $property = $this->createTestProperty();
        $event    = new AnonymizePropertyEvent(
            $this->em,
            $this->metadata,
            $property,
            'email',
            'original@example.com',
            'anonymized@example.com',
            ['id' => 1],
            false,
        );

        $this->assertSame($property, $event->getProperty());
    }

    public function testGetPropertyName(): void
    {
        $property = $this->createTestProperty();
        $event    = new AnonymizePropertyEvent(
            $this->em,
            $this->metadata,
            $property,
            'email',
            'original@example.com',
            'anonymized@example.com',
            ['id' => 1],
            false,
        );

        $this->assertEquals('testProperty', $event->getPropertyName());
    }

    public function testGetColumnName(): void
    {
        $property = $this->createTestProperty();
        $event    = new AnonymizePropertyEvent(
            $this->em,
            $this->metadata,
            $property,
            'email',
            'original@example.com',
            'anonymized@example.com',
            ['id' => 1],
            false,
        );

        $this->assertEquals('email', $event->getColumnName());
    }

    public function testGetOriginalValue(): void
    {
        $property = $this->createTestProperty();
        $event    = new AnonymizePropertyEvent(
            $this->em,
            $this->metadata,
            $property,
            'email',
            'original@example.com',
            'anonymized@example.com',
            ['id' => 1],
            false,
        );

        $this->assertEquals('original@example.com', $event->getOriginalValue());
    }

    public function testGetAnonymizedValue(): void
    {
        $property = $this->createTestProperty();
        $event    = new AnonymizePropertyEvent(
            $this->em,
            $this->metadata,
            $property,
            'email',
            'original@example.com',
            'anonymized@example.com',
            ['id' => 1],
            false,
        );

        $this->assertEquals('anonymized@example.com', $event->getAnonymizedValue());
    }

    public function testSetAnonymizedValue(): void
    {
        $property = $this->createTestProperty();
        $event    = new AnonymizePropertyEvent(
            $this->em,
            $this->metadata,
            $property,
            'email',
            'original@example.com',
            'anonymized@example.com',
            ['id' => 1],
            false,
        );

        $event->setAnonymizedValue('modified@example.com');
        $this->assertEquals('modified@example.com', $event->getAnonymizedValue());
    }

    public function testGetRecord(): void
    {
        $property = $this->createTestProperty();
        $record   = ['id' => 1, 'email' => 'original@example.com', 'name' => 'John'];
        $event    = new AnonymizePropertyEvent(
            $this->em,
            $this->metadata,
            $property,
            'email',
            'original@example.com',
            'anonymized@example.com',
            $record,
            false,
        );

        $this->assertEquals($record, $event->getRecord());
    }

    public function testShouldSkipAnonymization(): void
    {
        $property = $this->createTestProperty();
        $event    = new AnonymizePropertyEvent(
            $this->em,
            $this->metadata,
            $property,
            'email',
            'original@example.com',
            'anonymized@example.com',
            ['id' => 1],
            false,
        );

        $this->assertFalse($event->shouldSkipAnonymization());

        $event->setSkipAnonymization(true);
        $this->assertTrue($event->shouldSkipAnonymization());
    }

    public function testSetSkipAnonymization(): void
    {
        $property = $this->createTestProperty();
        $event    = new AnonymizePropertyEvent(
            $this->em,
            $this->metadata,
            $property,
            'email',
            'original@example.com',
            'anonymized@example.com',
            ['id' => 1],
            false,
        );

        $event->setSkipAnonymization(true);
        $this->assertTrue($event->shouldSkipAnonymization());

        $event->setSkipAnonymization(false);
        $this->assertFalse($event->shouldSkipAnonymization());
    }

    public function testIsDryRun(): void
    {
        $property = $this->createTestProperty();
        $event1   = new AnonymizePropertyEvent(
            $this->em,
            $this->metadata,
            $property,
            'email',
            'original@example.com',
            'anonymized@example.com',
            ['id' => 1],
            true,
        );
        $this->assertTrue($event1->isDryRun());

        $event2 = new AnonymizePropertyEvent(
            $this->em,
            $this->metadata,
            $property,
            'email',
            'original@example.com',
            'anonymized@example.com',
            ['id' => 1],
            false,
        );
        $this->assertFalse($event2->isDryRun());
    }
}
