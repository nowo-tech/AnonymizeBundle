<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized Spanish DNI, CIF, and NIF numbers.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.dni_cif')]
final class DniCifFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Letters used for DNI/NIF checksum calculation (mod 23).
     */
    private const DNI_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';

    /**
     * CIF letter prefixes (A, B, C, D, E, F, G, H, J, K, L, M, N, P, Q, R, S, U, V, W).
     */
    private const CIF_LETTERS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'U', 'V', 'W'];

    /**
     * Creates a new DniCifFaker instance.
     *
     * @param string $locale The locale for Faker generator
     */
    public function __construct(
        #[Autowire('%nowo_anonymize.locale%')]
        string $locale = 'en_US'
    ) {
        $this->faker = Factory::create($locale);
    }

    /**
     * Generates an anonymized DNI, CIF, or NIF.
     *
     * @param array<string, mixed> $options Options:
     *   - 'type' (string): 'dni', 'cif', 'nif', or 'auto' (default: 'auto')
     *     - 'dni': Generates DNI format (8 digits + 1 letter)
     *     - 'cif': Generates CIF format (1 letter + 7 digits + 1 letter/digit)
     *     - 'nif': Same as DNI (8 digits + 1 letter)
     *     - 'auto': Automatically detects type from original value if available, otherwise generates DNI
     *   - 'formatted' (bool): Include separator (default: false)
     * @return string The anonymized DNI/CIF/NIF
     */
    public function generate(array $options = []): string
    {
        $type = $options['type'] ?? 'auto';
        $formatted = $options['formatted'] ?? false;
        $originalValue = $options['original_value'] ?? null;

        // Auto-detect type from original value if available
        if ($type === 'auto' && $originalValue !== null && is_string($originalValue)) {
            $type = $this->detectType($originalValue);
        }

        // Default to DNI if auto-detection fails
        if ($type === 'auto') {
            $type = 'dni';
        }

        $result = match (strtolower($type)) {
            'dni', 'nif' => $this->generateDni(),
            'cif' => $this->generateCif(),
            default => $this->generateDni(),
        };

        // Format with separator if requested
        if ($formatted) {
            $result = $this->format($result, $type);
        }

        return $result;
    }

    /**
     * Generates a DNI/NIF (8 digits + 1 letter).
     *
     * @return string The generated DNI/NIF
     */
    private function generateDni(): string
    {
        // Generate 8 random digits
        $number = $this->faker->numberBetween(10000000, 99999999);
        
        // Calculate checksum letter (mod 23)
        $letterIndex = $number % 23;
        $letter = self::DNI_LETTERS[$letterIndex];

        return (string) $number . $letter;
    }

    /**
     * Generates a CIF (1 letter + 7 digits + 1 letter/digit).
     *
     * @return string The generated CIF
     */
    private function generateCif(): string
    {
        // First letter (random from valid CIF letters)
        $firstLetter = $this->faker->randomElement(self::CIF_LETTERS);

        // Generate 7 random digits
        $number = $this->faker->numberBetween(1000000, 9999999);

        // Calculate checksum (last character)
        $checksum = $this->calculateCifChecksum($number);

        return $firstLetter . (string) $number . $checksum;
    }

    /**
     * Calculates the CIF checksum character.
     *
     * @param int $number The 7-digit number
     * @return string The checksum character (letter or digit)
     */
    private function calculateCifChecksum(int $number): string
    {
        $digits = str_split((string) $number);
        $sum = 0;

        // Sum even positions (0-indexed, so positions 1, 3, 5)
        for ($i = 1; $i < 7; $i += 2) {
            $sum += (int) $digits[$i];
        }

        // Sum digits of odd positions multiplied by 2
        for ($i = 0; $i < 7; $i += 2) {
            $double = (int) $digits[$i] * 2;
            $sum += array_sum(str_split((string) $double));
        }

        $remainder = $sum % 10;
        $checksum = $remainder === 0 ? 0 : 10 - $remainder;

        // Convert to letter if checksum is 10 (shouldn't happen with our calculation, but for safety)
        if ($checksum === 10) {
            return 'J';
        }

        return (string) $checksum;
    }

    /**
     * Detects the type (DNI, CIF, NIF) from a value.
     *
     * @param string $value The value to analyze
     * @return string The detected type ('dni', 'cif', or 'nif')
     */
    private function detectType(string $value): string
    {
        // Remove spaces and convert to uppercase
        $value = strtoupper(trim(str_replace([' ', '-', '.'], '', $value)));

        // CIF: starts with a letter and has 8 characters total
        if (preg_match('/^[A-Z]\d{7}[A-Z0-9]$/', $value)) {
            return 'cif';
        }

        // DNI/NIF: 8 digits + 1 letter
        if (preg_match('/^\d{8}[A-Z]$/', $value)) {
            return 'dni';
        }

        // Default to DNI if pattern doesn't match
        return 'dni';
    }

    /**
     * Formats the DNI/CIF/NIF with separator.
     *
     * @param string $value The value to format
     * @param string $type The type ('dni', 'cif', 'nif')
     * @return string The formatted value
     */
    private function format(string $value, string $type): string
    {
        if (strtolower($type) === 'cif') {
            // CIF: A-1234567-4
            return substr($value, 0, 1) . '-' . substr($value, 1, 7) . '-' . substr($value, 8, 1);
        }

        // DNI/NIF: 12345678-A
        return substr($value, 0, 8) . '-' . substr($value, 8, 1);
    }
}
