<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Faker;

use InvalidArgumentException;
use Nowo\AnonymizeBundle\Faker\HashPreserveFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function strlen;

/**
 * Test case for HashPreserveFaker (keyed HMAC pseudonymisation).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class HashPreserveFakerTest extends TestCase
{
    /** Obvious test-only key (not a secret). */
    private const KEY = 'REDACTED-TEST-KEY-0000000000000000000';

    public function testGenerateIsAKeyedHmacSha256(): void
    {
        $hash = (new HashPreserveFaker(self::KEY))->generate(['original_value' => 'test@example.com']);

        $this->assertSame(hash_hmac('sha256', 'test@example.com', self::KEY), $hash);
        $this->assertNotSame(hash('sha256', 'test@example.com'), $hash);
    }

    public function testGenerateIsDeterministicAndDependsOnTheKey(): void
    {
        $faker = new HashPreserveFaker(self::KEY);
        $other = new HashPreserveFaker(self::KEY . '-other');

        $this->assertSame($faker->generate(['value' => 'a@b.test']), $faker->generate(['value' => 'a@b.test']));
        $this->assertNotSame($faker->generate(['value' => 'a@b.test']), $other->generate(['value' => 'a@b.test']));
    }

    public function testSha512(): void
    {
        $hash = (new HashPreserveFaker(self::KEY))->generate(['value' => 'test', 'algorithm' => 'sha512']);

        $this->assertSame(hash_hmac('sha512', 'test', self::KEY), $hash);
    }

    public function testSaltIsAContextMixedIntoTheMessage(): void
    {
        $faker = new HashPreserveFaker(self::KEY);

        $this->assertSame(hash_hmac('sha256', "users.email\0test", self::KEY), $faker->generate(['value' => 'test', 'salt' => 'users.email']));
        $this->assertSame($faker->generate(['value' => 'test']), $faker->generate(['value' => 'test', 'salt' => '']));
    }

    /**
     * @return iterable<string, array{string|null}>
     */
    public static function unusableKeyProvider(): iterable
    {
        yield 'missing' => [null];
        yield 'empty' => [''];
        yield 'too short' => ['short-key'];
        yield 'placeholder' => ['!ChangeMe!-padding-to-reach-thirty-two-chars'];
        yield 'your secret placeholder' => ['your-secret-padding-to-reach-thirty-two'];
    }

    #[DataProvider('unusableKeyProvider')]
    public function testUnusableKeyIsRejected(?string $key): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('hash_preserve needs a secret HMAC key of at least 32 characters');

        (new HashPreserveFaker($key))->generate(['value' => 'test']);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function unsupportedAlgorithmProvider(): iterable
    {
        yield 'md5' => ['md5'];
        yield 'sha1' => ['sha1'];
        yield 'not a string' => [['sha256']];
    }

    #[DataProvider('unsupportedAlgorithmProvider')]
    public function testWeakOrUnknownAlgorithmIsRejected(mixed $algorithm): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not supported; use "sha256" or "sha512"');

        (new HashPreserveFaker(self::KEY))->generate(['value' => 'test', 'algorithm' => $algorithm]);
    }

    public function testMissingValueIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires an "original_value"');

        (new HashPreserveFaker(self::KEY))->generate([]);
    }

    public function testLengthTruncatesAndNonPositiveLengthIsIgnored(): void
    {
        $faker = new HashPreserveFaker(self::KEY);

        $this->assertSame(10, strlen($faker->generate(['value' => 'test', 'length' => 10])));
        $this->assertSame(64, strlen($faker->generate(['value' => 'test', 'length' => 0])));
        $this->assertSame(64, strlen($faker->generate(['value' => 'test', 'length' => -5])));
    }

    public function testPreserveFormatKeepsDigitsForNumericValuesOnly(): void
    {
        $faker = new HashPreserveFaker(self::KEY);

        $this->assertMatchesRegularExpression('/^\d{1,20}$/', $faker->generate(['value' => 12345, 'preserve_format' => true]));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $faker->generate(['value' => 'abc', 'preserve_format' => true]));
    }

    public function testPreserveFormatCapsLongDigitRunsAt20(): void
    {
        $faker = new HashPreserveFaker(self::KEY);

        // Find a numeric value whose HMAC has more than 20 digits
        $value = 0;
        while (strlen((string) preg_replace('/\D/', '', hash_hmac('sha256', (string) $value, self::KEY))) <= 20) {
            ++$value;
        }

        $this->assertSame(20, strlen($faker->generate(['value' => $value, 'preserve_format' => true])));
    }

    public function testPreserveFormatFallsBackToZeroWhenTheTruncatedHashHasNoDigit(): void
    {
        $faker = new HashPreserveFaker(self::KEY);

        // Find a value whose first HMAC character is a letter: truncated to 1 char it has no digit
        $value = 0;
        while (ctype_digit(substr(hash_hmac('sha256', (string) $value, self::KEY), 0, 1))) {
            ++$value;
        }

        $this->assertSame('0', $faker->generate(['value' => $value, 'length' => 1, 'preserve_format' => true]));
    }
}
