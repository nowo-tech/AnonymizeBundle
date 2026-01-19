<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests;

use Nowo\AnonymizeBundle\AnonymizeBundle;
use Nowo\AnonymizeBundle\DependencyInjection\AnonymizeExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * Test case for AnonymizeBundle.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizeBundleTest extends TestCase
{
    /**
     * Test that the bundle returns the correct extension.
     */
    public function testGetContainerExtension(): void
    {
        $bundle = new AnonymizeBundle();
        $extension = $bundle->getContainerExtension();

        $this->assertInstanceOf(ExtensionInterface::class, $extension);
        $this->assertInstanceOf(AnonymizeExtension::class, $extension);
    }

    /**
     * Test that the bundle returns the same extension instance on multiple calls.
     */
    public function testGetContainerExtensionReturnsSameInstance(): void
    {
        $bundle = new AnonymizeBundle();
        $extension1 = $bundle->getContainerExtension();
        $extension2 = $bundle->getContainerExtension();

        $this->assertSame($extension1, $extension2);
    }
}
