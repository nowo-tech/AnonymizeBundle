<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\DependencyInjection\Fixtures;

use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Faker\FakerFactoryInterface;

/**
 * App-like service that type-hints the faker factory (autowiring fixture).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
final readonly class FakerFactoryConsumer
{
    public function __construct(
        public FakerFactory $fakerFactory,
        public FakerFactoryInterface $fakerFactoryInterface,
    ) {
    }
}
