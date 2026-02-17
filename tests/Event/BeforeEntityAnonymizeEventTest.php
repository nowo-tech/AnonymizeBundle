<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nowo\AnonymizeBundle\Event\BeforeEntityAnonymizeEvent;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test case for BeforeEntityAnonymizeEvent.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class BeforeEntityAnonymizeEventTest extends TestCase
{
    public function testGetEntityManager(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $metadata   = $this->createMock(ClassMetadata::class);
        $reflection = new ReflectionClass($this);

        $event = new BeforeEntityAnonymizeEvent($em, $metadata, $reflection, 10, false);

        $this->assertSame($em, $event->getEntityManager());
    }

    public function testGetMetadata(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $metadata   = $this->createMock(ClassMetadata::class);
        $reflection = new ReflectionClass($this);

        $event = new BeforeEntityAnonymizeEvent($em, $metadata, $reflection, 10, false);

        $this->assertSame($metadata, $event->getMetadata());
    }

    public function testGetReflection(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $metadata   = $this->createMock(ClassMetadata::class);
        $reflection = new ReflectionClass($this);

        $event = new BeforeEntityAnonymizeEvent($em, $metadata, $reflection, 10, false);

        $this->assertSame($reflection, $event->getReflection());
    }

    public function testGetTotalRecords(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $metadata   = $this->createMock(ClassMetadata::class);
        $reflection = new ReflectionClass($this);

        $event = new BeforeEntityAnonymizeEvent($em, $metadata, $reflection, 100, false);

        $this->assertEquals(100, $event->getTotalRecords());
    }

    public function testGetEntityClass(): void
    {
        $em       = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getName')
            ->willReturn('App\Entity\User');
        $reflection = new ReflectionClass($this);

        $event = new BeforeEntityAnonymizeEvent($em, $metadata, $reflection, 10, false);

        $this->assertEquals('App\Entity\User', $event->getEntityClass());
    }

    public function testIsDryRun(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $metadata   = $this->createMock(ClassMetadata::class);
        $reflection = new ReflectionClass($this);

        $event1 = new BeforeEntityAnonymizeEvent($em, $metadata, $reflection, 10, true);
        $this->assertTrue($event1->isDryRun());

        $event2 = new BeforeEntityAnonymizeEvent($em, $metadata, $reflection, 10, false);
        $this->assertFalse($event2->isDryRun());
    }
}
