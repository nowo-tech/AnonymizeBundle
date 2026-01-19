<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Enum;

/**
 * Enum class for Symfony service names.
 *
 * This class contains constants for service names used by the bundle.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
abstract class SymfonyService
{
    /**
     * Doctrine service name in Symfony container.
     */
    public const DOCTRINE = 'doctrine';
}
