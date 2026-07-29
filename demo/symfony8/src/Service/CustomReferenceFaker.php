<?php

declare(strict_types=1);

namespace App\Service;

use Nowo\AnonymizeBundle\Faker\FakerInterface;
use Nowo\AnonymizeBundle\Faker\FakerOptions;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Custom faker service for generating anonymized reference codes.
 *
 * This service demonstrates how to create a custom anonymizer that implements
 * FakerInterface and can be used with the 'service' faker type.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[Autoconfigure(public: true)]
final class CustomReferenceFaker implements FakerInterface
{
    /**
     * Generates an anonymized reference code.
     *
     * @param FakerOptions|array<string, mixed> $options Options:
     *                                                   - 'prefix' (string): Prefix for the reference (default: 'REF')
     *                                                   - 'length' (int): Length of the numeric part (default: 8)
     *                                                   - 'separator' (string): Separator between prefix and number (default: '-')
     *
     * @return string The anonymized reference code
     */
    public function generate(FakerOptions|array $options = []): string
    {
        $opts      = FakerOptions::normalize($options)->all();
        $prefix    = $opts['prefix'] ?? 'REF';
        $length    = (int) ($opts['length'] ?? 8);
        $separator = $opts['separator'] ?? '-';

        // Generate random numeric part
        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_repeat('9', $length);

        $number = (string) random_int($min, $max);

        return $prefix . $separator . $number;
    }
}
