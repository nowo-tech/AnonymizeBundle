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
     * @param array<string, mixed>|FakerOptions $options Options for the faker. Prefer FakerOptions; array accepted for BC.
     *                                                   All fakers receive:
     *                                                   - 'original_value' (mixed): The original value from the database (always provided)
     *                                                   - Additional faker-specific options as documented in each faker class
     *
     * @return mixed The anonymized value
     */
    public function generate(FakerOptions|array $options = []): mixed;
}
