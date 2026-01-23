<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Event;

use Doctrine\ORM\EntityManagerInterface;
use Nowo\AnonymizeBundle\Event\AfterAnonymizeEvent;
use PHPUnit\Framework\TestCase;

/**
 * Test case for AfterAnonymizeEvent.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AfterAnonymizeEventTest extends TestCase
{
    public function testGetEntityManager(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $event = new AfterAnonymizeEvent($em, ['App\Entity\User'], 10, 8, false);

        $this->assertSame($em, $event->getEntityManager());
    }

    public function testGetEntityClasses(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $entityClasses = ['App\Entity\User', 'App\Entity\Customer'];
        $event = new AfterAnonymizeEvent($em, $entityClasses, 10, 8, false);

        $this->assertEquals($entityClasses, $event->getEntityClasses());
    }

    public function testGetTotalProcessed(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $event = new AfterAnonymizeEvent($em, ['App\Entity\User'], 100, 80, false);

        $this->assertEquals(100, $event->getTotalProcessed());
    }

    public function testGetTotalUpdated(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $event = new AfterAnonymizeEvent($em, ['App\Entity\User'], 100, 80, false);

        $this->assertEquals(80, $event->getTotalUpdated());
    }

    public function testIsDryRun(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $event1 = new AfterAnonymizeEvent($em, ['App\Entity\User'], 10, 8, true);
        $this->assertTrue($event1->isDryRun());

        $event2 = new AfterAnonymizeEvent($em, ['App\Entity\User'], 10, 8, false);
        $this->assertFalse($event2->isDryRun());
    }
}
