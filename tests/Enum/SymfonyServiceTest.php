<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Enum;

use Nowo\AnonymizeBundle\Enum\SymfonyService;
use PHPUnit\Framework\TestCase;

/**
 * Test case for SymfonyService class.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class SymfonyServiceTest extends TestCase
{
    /**
     * Test that DOCTRINE constant has correct value.
     */
    public function testDoctrineConstantHasCorrectValue(): void
    {
        $this->assertEquals('doctrine', SymfonyService::DOCTRINE);
    }

    /**
     * Test that SymfonyService is abstract and cannot be instantiated.
     */
    public function testSymfonyServiceIsAbstract(): void
    {
        $reflection = new \ReflectionClass(SymfonyService::class);
        $this->assertTrue($reflection->isAbstract());
    }

    /**
     * Test that all constants are accessible.
     */
    public function testAllConstantsAreAccessible(): void
    {
        $reflection = new \ReflectionClass(SymfonyService::class);
        $constants = $reflection->getConstants();
        
        $this->assertArrayHasKey('DOCTRINE', $constants);
        $this->assertEquals('doctrine', $constants['DOCTRINE']);
    }
}
