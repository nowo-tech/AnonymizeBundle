<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nowo\AnonymizeBundle\Event\AnonymizePropertyEvent;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Tests the listener/subscriber pattern for AnonymizePropertyEvent.
 *
 * Validates the contract used by listeners (e.g. demo AnonymizePropertySubscriber):
 * they receive the event before a property is anonymized and can read original value,
 * column name, record, and can modify the anonymized value or skip anonymization.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizePropertyListenerTest extends TestCase
{
    private EntityManagerInterface $em;
    private ClassMetadata $metadata;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->metadata = $this->createMock(ClassMetadata::class);
    }

    private function createTestProperty(): ReflectionProperty
    {
        $testClass = new class {
            public string $fileUrl = '';
        };
        return new ReflectionProperty($testClass, 'fileUrl');
    }

    private function createEvent(
        string $columnName,
        mixed $originalValue,
        mixed $anonymizedValue,
        array $record = [],
        bool $dryRun = false
    ): AnonymizePropertyEvent {
        $property = $this->createTestProperty();
        $record = $record ?: ['id' => 1, $columnName => $originalValue];

        return new AnonymizePropertyEvent(
            $this->em,
            $this->metadata,
            $property,
            $columnName,
            $originalValue,
            $anonymizedValue,
            $record,
            $dryRun
        );
    }

    /**
     * Listener that does nothing (like the demo subscriber with only commented code).
     */
    public function testListenerCanBeInvokedWithoutModifyingEvent(): void
    {
        $event = $this->createEvent('file_url', 'https://s3.amazonaws.com/bucket/key.pdf', 'https://anon.example/file.pdf');

        $listener = function (AnonymizePropertyEvent $e): void {
            $e->getOriginalValue();
            $e->getPropertyName();
            $e->getColumnName();
            $e->getRecord();
            // no setAnonymizedValue / setSkipAnonymization
        };
        $listener($event);

        $this->assertSame('https://s3.amazonaws.com/bucket/key.pdf', $event->getOriginalValue());
        $this->assertSame('https://anon.example/file.pdf', $event->getAnonymizedValue());
        $this->assertFalse($event->shouldSkipAnonymization());
    }

    /**
     * Listener can replace the anonymized value (e.g. after migrating file from S3 to new storage).
     */
    public function testListenerCanSetAnonymizedValue(): void
    {
        $event = $this->createEvent('file_url', 'https://s3.amazonaws.com/bucket/doc.pdf', 'https://anon.example/doc.pdf');

        $listener = function (AnonymizePropertyEvent $e): void {
            $original = $e->getOriginalValue();
            if ($original !== null && $original !== '') {
                // Simulate: download from S3, upload elsewhere, then set new URL
                $e->setAnonymizedValue('https://new-storage.example/migrated/doc.pdf');
            }
        };
        $listener($event);

        $this->assertSame('https://new-storage.example/migrated/doc.pdf', $event->getAnonymizedValue());
        $this->assertSame('https://s3.amazonaws.com/bucket/doc.pdf', $event->getOriginalValue());
    }

    /**
     * Listener can skip anonymization for this property.
     */
    public function testListenerCanSkipAnonymization(): void
    {
        $event = $this->createEvent('file_url', 'https://s3.amazonaws.com/bucket/keep.pdf', 'https://anon.example/keep.pdf');

        $listener = function (AnonymizePropertyEvent $e): void {
            $e->setSkipAnonymization(true);
        };
        $listener($event);

        $this->assertTrue($event->shouldSkipAnonymization());
    }

    /**
     * Listener can read full record (e.g. to decide by entity or other columns).
     */
    public function testListenerCanReadRecord(): void
    {
        $record = ['id' => 42, 'file_url' => 's3://bucket/x.pdf', 'entity_type' => 'Document'];
        $event = $this->createEvent('file_url', 's3://bucket/x.pdf', 'anon.pdf', $record);

        $listener = function (AnonymizePropertyEvent $e): void {
            $r = $e->getRecord();
            if (($r['entity_type'] ?? '') === 'Document') {
                $e->setAnonymizedValue('https://new-cdn.example/doc/42.pdf');
            }
        };
        $listener($event);

        $this->assertSame('https://new-cdn.example/doc/42.pdf', $event->getAnonymizedValue());
        $this->assertSame(42, $event->getRecord()['id']);
    }
}
