<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Faker for setting values to null.
 *
 * This faker always returns null, useful for clearing sensitive data
 * or making fields nullable. Can be used with `bypass_entity_exclusion` option
 * to set fields to null even when the record is excluded at entity level.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.null')]
final class NullFaker implements FakerInterface
{
    /**
     * Generates null value.
     *
     * @param array<string, mixed> $options Options: None required, this faker always returns null
     * @return null Always returns null
     */
    public function generate(array $options = []): mixed
    {
        return null;
    }
}
