<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

use function get_debug_type;
use function in_array;
use function is_scalar;
use function is_string;
use function sprintf;
use function strlen;

/**
 * Deterministic pseudonymisation with a keyed HMAC.
 *
 * The same input always produces the same output (referential integrity across tables), so the result is
 * **pseudonymous, not anonymous**: anyone holding the key can confirm a guessed value. The key must be a
 * dedicated secret (nowo_anonymize.hash_preserve.key / NOWO_ANONYMIZE_HASH_KEY), never committed.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.hash_preserve')]
#[Autoconfigure(public: true)]
final readonly class HashPreserveFaker implements FakerInterface
{
    /** Minimum key length: shorter keys are treated as placeholders. */
    public const MIN_KEY_LENGTH = 32;

    private const ALGORITHMS = ['sha256', 'sha512'];

    /**
     * @param string|null $key HMAC key (nowo_anonymize.hash_preserve.key, defaults to the NOWO_ANONYMIZE_HASH_KEY env var)
     */
    public function __construct(
        private ?string $key = null,
    ) {
    }

    /**
     * Generates a deterministic HMAC of the original value.
     *
     * @param array<string, mixed> $options Options:
     *                                      - 'original_value' (mixed): The original value to hash (standard, always provided).
     *                                      - 'value' (mixed): Alias for 'original_value' (backward compatibility).
     *                                      - 'algorithm' (string): 'sha256' (default) or 'sha512'.
     *                                      - 'salt' (string): Optional context mixed into the HMAC message (e.g. per column), not a secret.
     *                                      - 'preserve_format' (bool): Keep only digits for numeric originals (reduces entropy).
     *                                      - 'length' (int|null): Truncate the output (reduces entropy).
     *
     * @throws InvalidArgumentException when the value is missing, the key is missing / a placeholder, or the algorithm is unsupported
     *
     * @return string the pseudonymised value
     */
    public function generate(FakerOptions|array $options = []): string
    {
        $opts = FakerOptions::normalize($options)->all();
        // Support both 'original_value' (standard) and 'value' (backward compatibility)
        $value = $opts['original_value'] ?? $opts['value'] ?? null;

        if ($value === null) {
            throw new InvalidArgumentException('HashPreserveFaker requires an "original_value" (or "value") option with the original value to hash.');
        }

        $key       = $this->usableKey();
        $algorithm = $opts['algorithm'] ?? 'sha256';
        if (!in_array($algorithm, self::ALGORITHMS, true)) {
            throw new InvalidArgumentException(sprintf('HashPreserveFaker algorithm "%s" is not supported; use "sha256" or "sha512" (md5 and sha1 were removed).', is_scalar($algorithm) ? (string) $algorithm : get_debug_type($algorithm)));
        }
        $context        = $opts['salt'] ?? '';
        $preserveFormat = $opts['preserve_format'] ?? false;
        $length         = $opts['length'] ?? null;

        // Context (former "salt") separates domains; the secret is the HMAC key
        $message = is_string($context) && $context !== '' ? $context . "\0" . $value : (string) $value;
        $hash    = hash_hmac($algorithm, $message, $key);

        // Truncate if length specified
        if ($length !== null && $length > 0) {
            $hash = substr($hash, 0, (int) $length);
        }

        // Preserve format if requested
        if ($preserveFormat && is_numeric($value)) {
            // If original was numeric, try to make hash numeric-like
            $hash = preg_replace('/[^0-9]/', '', $hash);
            if (empty($hash)) {
                $hash = '0';
            }
            // Limit to reasonable length for numeric
            if (strlen($hash) > 20) {
                $hash = substr($hash, 0, 20);
            }
        }

        return $hash;
    }

    /**
     * @throws InvalidArgumentException when no usable key is configured
     */
    private function usableKey(): string
    {
        $key = $this->key ?? '';
        if (strlen($key) < self::MIN_KEY_LENGTH || preg_match('/change_?me|placeholder|your[_-]?(secret|key)/i', $key) === 1) {
            throw new InvalidArgumentException(sprintf('hash_preserve needs a secret HMAC key of at least %d characters: set nowo_anonymize.hash_preserve.key or the NOWO_ANONYMIZE_HASH_KEY environment variable (e.g. "php -r \'echo bin2hex(random_bytes(32));\'"). Placeholder values are rejected.', self::MIN_KEY_LENGTH));
        }

        return $key;
    }
}
