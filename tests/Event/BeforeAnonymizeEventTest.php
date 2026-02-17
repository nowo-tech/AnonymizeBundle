<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Event;

use Doctrine\ORM\EntityManagerInterface;
use Nowo\AnonymizeBundle\Event\BeforeAnonymizeEvent;
use PHPUnit\Framework\TestCase;

/**
 * Test case for BeforeAnonymizeEvent.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class BeforeAnonymizeEventTest extends TestCase
{
    public function testGetEntityManager(): void
    {
        $em    = $this->createMock(EntityManagerInterface::class);
        $event = new BeforeAnonymizeEvent($em, ['App\Entity\User'], false);

        $this->assertSame($em, $event->getEntityManager());
    }

    public function testGetEntityClasses(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $entityClasses = ['App\Entity\User', 'App\Entity\Customer'];
        $event         = new BeforeAnonymizeEvent($em, $entityClasses, false);

        $this->assertEquals($entityClasses, $event->getEntityClasses());
    }

    public function testSetEntityClasses(): void
    {
        $em    = $this->createMock(EntityManagerInterface::class);
        $event = new BeforeAnonymizeEvent($em, ['App\Entity\User'], false);

        $newClasses = ['App\Entity\Product'];
        $event->setEntityClasses($newClasses);

        $this->assertEquals($newClasses, $event->getEntityClasses());
    }

    public function testIsDryRun(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $event1 = new BeforeAnonymizeEvent($em, ['App\Entity\User'], true);
        $this->assertTrue($event1->isDryRun());

        $event2 = new BeforeAnonymizeEvent($em, ['App\Entity\User'], false);
        $this->assertFalse($event2->isDryRun());
    }
}
