<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\DependencyInjection\Compiler\Fixtures;

use Nowo\AnonymizeBundle\Faker\FakerInterface;
use Nowo\AnonymizeBundle\Faker\FakerOptions;

/**
 * Custom faker implementing FakerInterface (picked up whatever its visibility).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final class InterfaceFaker implements FakerInterface
{
    public function generate(FakerOptions|array $options = []): mixed
    {
        return 'interface-faker';
    }
}
