<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Trait;

use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;
use PHPUnit\Framework\TestCase;

/**
 * Test case for AnonymizableTrait.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizableTraitTest extends TestCase
{
    /**
     * Test that isAnonymized returns false by default.
     */
    public function testIsAnonymizedReturnsFalseByDefault(): void
    {
        $entity = new class {
            use AnonymizableTrait;
        };

        $this->assertFalse($entity->isAnonymized());
    }

    /**
     * Test that setAnonymized sets the anonymized flag to true.
     */
    public function testSetAnonymizedSetsToTrue(): void
    {
        $entity = new class {
            use AnonymizableTrait;
        };

        $result = $entity->setAnonymized(true);

        $this->assertTrue($entity->isAnonymized());
        $this->assertSame($entity, $result); // Method chaining
    }

    /**
     * Test that setAnonymized sets the anonymized flag to false.
     */
    public function testSetAnonymizedSetsToFalse(): void
    {
        $entity = new class {
            use AnonymizableTrait;
        };

        $entity->setAnonymized(true);
        $this->assertTrue($entity->isAnonymized());

        $result = $entity->setAnonymized(false);

        $this->assertFalse($entity->isAnonymized());
        $this->assertSame($entity, $result); // Method chaining
    }

    /**
     * Test that setAnonymized supports method chaining.
     */
    public function testSetAnonymizedSupportsMethodChaining(): void
    {
        $entity = new class {
            use AnonymizableTrait;
        };

        $result = $entity->setAnonymized(true)->setAnonymized(false);

        $this->assertFalse($entity->isAnonymized());
        $this->assertSame($entity, $result);
    }

    /**
     * Test that isAnonymized returns correct value after multiple changes.
     */
    public function testIsAnonymizedReturnsCorrectValueAfterMultipleChanges(): void
    {
        $entity = new class {
            use AnonymizableTrait;
        };

        $this->assertFalse($entity->isAnonymized());

        $entity->setAnonymized(true);
        $this->assertTrue($entity->isAnonymized());

        $entity->setAnonymized(false);
        $this->assertFalse($entity->isAnonymized());

        $entity->setAnonymized(true);
        $this->assertTrue($entity->isAnonymized());
    }

    /**
     * Test that trait works with entity that has other properties.
     */
    public function testTraitWorksWithEntityWithOtherProperties(): void
    {
        $entity = new class {
            use AnonymizableTrait;

            public string $name = 'John';
            public string $email = 'john@example.com';
        };

        $this->assertFalse($entity->isAnonymized());
        $this->assertEquals('John', $entity->name);
        $this->assertEquals('john@example.com', $entity->email);

        $entity->setAnonymized(true);
        $this->assertTrue($entity->isAnonymized());
        $this->assertEquals('John', $entity->name);
        $this->assertEquals('john@example.com', $entity->email);
    }

    /**
     * Test that multiple instances have independent anonymized state.
     */
    public function testMultipleInstancesHaveIndependentState(): void
    {
        $entity1 = new class {
            use AnonymizableTrait;
        };

        $entity2 = new class {
            use AnonymizableTrait;
        };

        $this->assertFalse($entity1->isAnonymized());
        $this->assertFalse($entity2->isAnonymized());

        $entity1->setAnonymized(true);

        $this->assertTrue($entity1->isAnonymized());
        $this->assertFalse($entity2->isAnonymized());

        $entity2->setAnonymized(true);

        $this->assertTrue($entity1->isAnonymized());
        $this->assertTrue($entity2->isAnonymized());
    }
}
