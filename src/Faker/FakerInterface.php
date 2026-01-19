<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

/**
 * Interface for faker generators.
 *
 * All faker implementations must implement this interface to provide
 * anonymization functionality for different data types.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
interface FakerInterface
{
    /**
     * Generates an anonymized value.
     *
     * @param array<string, mixed> $options Additional options for the faker
     * @return mixed The anonymized value
     */
    public function generate(array $options = []): mixed;
}
