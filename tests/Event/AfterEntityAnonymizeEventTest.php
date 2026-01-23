<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nowo\AnonymizeBundle\Event\AfterEntityAnonymizeEvent;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test case for AfterEntityAnonymizeEvent.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AfterEntityAnonymizeEventTest extends TestCase
{
    public function testGetEntityManager(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $reflection = new ReflectionClass($this);

        $event = new AfterEntityAnonymizeEvent($em, $metadata, $reflection, 10, 8, [], false);

        $this->assertSame($em, $event->getEntityManager());
    }

    public function testGetMetadata(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $reflection = new ReflectionClass($this);

        $event = new AfterEntityAnonymizeEvent($em, $metadata, $reflection, 10, 8, [], false);

        $this->assertSame($metadata, $event->getMetadata());
    }

    public function testGetReflection(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $reflection = new ReflectionClass($this);

        $event = new AfterEntityAnonymizeEvent($em, $metadata, $reflection, 10, 8, [], false);

        $this->assertSame($reflection, $event->getReflection());
    }

    public function testGetProcessed(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $reflection = new ReflectionClass($this);

        $event = new AfterEntityAnonymizeEvent($em, $metadata, $reflection, 100, 80, ['email' => 80], false);

        $this->assertEquals(100, $event->getProcessed());
    }

    public function testGetUpdated(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $reflection = new ReflectionClass($this);

        $event = new AfterEntityAnonymizeEvent($em, $metadata, $reflection, 100, 80, ['email' => 80], false);

        $this->assertEquals(80, $event->getUpdated());
    }


    public function testGetPropertyStats(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $reflection = new ReflectionClass($this);
        $propertyStats = ['email' => 80, 'name' => 75];

        $event = new AfterEntityAnonymizeEvent($em, $metadata, $reflection, 100, 80, $propertyStats, false);

        $this->assertEquals($propertyStats, $event->getPropertyStats());
    }

    public function testGetEntityClass(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getName')
            ->willReturn('App\Entity\User');
        $reflection = new ReflectionClass($this);

        $event = new AfterEntityAnonymizeEvent($em, $metadata, $reflection, 10, 8, [], false);

        $this->assertEquals('App\Entity\User', $event->getEntityClass());
    }

    public function testIsDryRun(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $reflection = new ReflectionClass($this);

        $event1 = new AfterEntityAnonymizeEvent($em, $metadata, $reflection, 10, 8, [], true);
        $this->assertTrue($event1->isDryRun());

        $event2 = new AfterEntityAnonymizeEvent($em, $metadata, $reflection, 10, 8, [], false);
        $this->assertFalse($event2->isDryRun());
    }
}
