<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use function array_key_exists;

/**
 * Typed options bag for faker generators (REQ-PHP-001).
 *
 * Always includes `original_value` when provided by the anonymization engine.
 * Faker-specific keys are documented on each faker class.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final readonly class FakerOptions
{
    /** @param array<string, mixed> $values */
    public function __construct(private array $values = [])
    {
    }

    /** @param array<string, mixed> $values */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    /** @param array<string, mixed>|self $options */
    public static function normalize(array|self $options): self
    {
        return $options instanceof self ? $options : self::fromArray($options);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->values;
    }

    public function with(string $key, mixed $value): self
    {
        $values       = $this->values;
        $values[$key] = $value;

        return new self($values);
    }

    /** @param array<string, mixed> $extra */
    public function merge(array $extra): self
    {
        return new self([...$this->values, ...$extra]);
    }
}
