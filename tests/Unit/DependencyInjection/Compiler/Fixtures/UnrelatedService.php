<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\DependencyInjection\Compiler\Fixtures;

/**
 * Unrelated application service (must never enter the plugin locator).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class UnrelatedService
{
    /**
     * Private generate() does not match the duck-typed contract.
     */
    private function generate(): void
    {
    }

    public function run(): void
    {
        $this->generate();
    }
}
