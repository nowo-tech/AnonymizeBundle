<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Nowo\AnonymizeBundle\Enum\FakerType;

/**
 * Interface for creating faker instances by type.
 *
 * Allows tests to substitute a fake implementation that throws
 * (e.g. to cover exception handling in PreFlightCheckService).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
interface FakerFactoryInterface
{
    /**
     * Creates a faker instance for the given type.
     *
     * @param FakerType|string $type The faker type
     * @param string|null $serviceName The service name if type is 'service'
     *
     * @return FakerInterface The faker instance
     */
    public function create(FakerType|string $type, ?string $serviceName = null): FakerInterface;
}
