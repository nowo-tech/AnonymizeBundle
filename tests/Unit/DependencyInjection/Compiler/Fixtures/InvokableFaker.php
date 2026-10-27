<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\DependencyInjection\Compiler\Fixtures;

/**
 * Invokable custom faker (callable contract).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class InvokableFaker
{
    public function __invoke(): string
    {
        return 'invokable';
    }
}
