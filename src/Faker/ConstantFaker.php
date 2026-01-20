<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Faker for replacing values with a constant.
 *
 * This faker replaces all values with a specified constant value,
 * useful for completely anonymizing sensitive data.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.constant')]
#[Autoconfigure(public: true)]
final class ConstantFaker implements FakerInterface
{
    /**
     * Creates a new ConstantFaker instance.
     */
    public function __construct()
    {
    }

    /**
     * Generates a constant value.
     *
     * @param array<string, mixed> $options Options:
     *   - 'value' (mixed): The constant value to return (required).
     * @return mixed The constant value.
     * @throws \InvalidArgumentException If 'value' option is missing.
     */
    public function generate(array $options = []): mixed
    {
        if (!array_key_exists('value', $options)) {
            throw new \InvalidArgumentException('ConstantFaker requires a "value" option specifying the constant value to use.');
        }

        return $options['value'];
    }
}
