<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\DependencyInjection\Compiler\Fixtures;

/**
 * Duck-typed custom faker (generate() without FakerInterface).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class DuckTypedFaker
{
    public function generate(): string
    {
        return 'duck-typed';
    }
}
