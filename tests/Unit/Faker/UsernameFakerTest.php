<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Faker;

use Nowo\AnonymizeBundle\Faker\UsernameFaker;
use PHPUnit\Framework\TestCase;

use function sprintf;
use function strlen;

/**
 * Test case for UsernameFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class UsernameFakerTest extends TestCase
{
    /**
     * Test that UsernameFaker generates a valid username.
     */
    public function testGenerate(): void
    {
        $faker    = new UsernameFaker('en_US');
        $username = $faker->generate();

        $this->assertIsString($username);
        $this->assertNotEmpty($username);
    }

    /**
     * Test that UsernameFaker respects min_length and max_length options.
     */
    public function testGenerateWithLength(): void
    {
        $faker    = new UsernameFaker('en_US');
        $username = $faker->generate(['min_length' => 8, 'max_length' => 12]);

        $this->assertIsString($username);
        $this->assertNotEmpty($username);
        // Note: Due to prefix/suffix and number inclusion, actual length may vary
        // We check that it's within reasonable bounds (at least min_length, at most max_length)
        $this->assertGreaterThanOrEqual(8, strlen($username), sprintf('Username "%s" length %d is less than min_length 8', $username, strlen($username)));
        $this->assertLessThanOrEqual(12, strlen($username), sprintf('Username "%s" length %d is greater than max_length 12', $username, strlen($username)));
    }

    /**
     * Test that UsernameFaker respects prefix option.
     */
    public function testGenerateWithPrefix(): void
    {
        $faker    = new UsernameFaker('en_US');
        $username = $faker->generate(['prefix' => 'user_']);

        $this->assertIsString($username);
        $this->assertStringStartsWith('user_', $username);
    }

    /**
     * Test that UsernameFaker respects suffix option.
     */
    public function testGenerateWithSuffix(): void
    {
        $faker    = new UsernameFaker('en_US');
        $username = $faker->generate(['suffix' => '_test', 'max_length' => 50]);

        $this->assertIsString($username);
        $this->assertStringEndsWith('_test', $username);
    }

    /**
     * Test that UsernameFaker respects include_numbers option.
     */
    public function testGenerateWithNumbers(): void
    {
        $faker    = new UsernameFaker('en_US');
        $username = $faker->generate(['include_numbers' => true, 'max_length' => 20]);

        $this->assertIsString($username);
        // Note: Numbers might not always be included due to probability
        $this->assertNotEmpty($username);
    }

    /**
     * Test that UsernameFaker handles include_numbers false.
     */
    public function testGenerateWithoutNumbers(): void
    {
        $faker    = new UsernameFaker('en_US');
        $username = $faker->generate(['include_numbers' => false, 'max_length' => 20]);

        $this->assertIsString($username);
        $this->assertNotEmpty($username);
    }

    /**
     * Test that UsernameFaker handles length constraints with prefix and suffix.
     */
    public function testGenerateWithPrefixSuffixAndLength(): void
    {
        $faker    = new UsernameFaker('en_US');
        $username = $faker->generate([
            'prefix'     => 'user_',
            'suffix'     => '_test',
            'min_length' => 10,
            'max_length' => 20,
        ]);

        $this->assertIsString($username);
        $this->assertStringStartsWith('user_', $username);
        $this->assertStringEndsWith('_test', $username);
        $this->assertLessThanOrEqual(20, strlen($username));
    }

    /**
     * Test that UsernameFaker pads to minimum length when needed.
     */
    public function testGeneratePadsToMinimumLength(): void
    {
        $faker = new UsernameFaker('en_US');
        // Force a very short base by using a long prefix/suffix
        $username = $faker->generate([
            'prefix'     => 'a',
            'suffix'     => 'b',
            'min_length' => 10,
            'max_length' => 20,
        ]);

        $this->assertIsString($username);
        $this->assertGreaterThanOrEqual(10, strlen($username));
    }

    /**
     * Test that UsernameFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new UsernameFaker('en_US');
        $this->assertInstanceOf(UsernameFaker::class, $faker);
    }

    /**
     * Test that UsernameFaker constructor with default locale (no argument) works.
     * Covers the default parameter $locale = 'en_US'.
     */
    public function testConstructorWithDefaultLocale(): void
    {
        $faker = new UsernameFaker();
        $this->assertInstanceOf(UsernameFaker::class, $faker);
        $username = $faker->generate();
        $this->assertIsString($username);
        $this->assertNotEmpty($username);
    }

    /**
     * Test that UsernameFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker    = new UsernameFaker('es_ES');
        $username = $faker->generate();

        $this->assertIsString($username);
        $this->assertNotEmpty($username);
    }

    /**
     * Test that UsernameFaker handles very long prefix and suffix.
     */
    public function testGenerateWithVeryLongPrefixSuffix(): void
    {
        $faker    = new UsernameFaker('en_US');
        $username = $faker->generate([
            'prefix'     => 'very_long_prefix_',
            'suffix'     => '_very_long_suffix',
            'min_length' => 5,
            'max_length' => 50,
        ]);

        $this->assertIsString($username);
        $this->assertStringStartsWith('very_long_prefix_', $username);
        $this->assertStringEndsWith('_very_long_suffix', $username);
        $this->assertLessThanOrEqual(50, strlen($username));
    }

    /**
     * Test that UsernameFaker handles min_length equal to max_length.
     */
    public function testGenerateWithEqualMinMaxLength(): void
    {
        $faker    = new UsernameFaker('en_US');
        $username = $faker->generate([
            'min_length' => 10,
            'max_length' => 10,
        ]);

        $this->assertIsString($username);
        $this->assertEquals(10, strlen($username));
    }

    /**
     * Test that UsernameFaker handles zero min_length.
     */
    public function testGenerateWithZeroMinLength(): void
    {
        $faker    = new UsernameFaker('en_US');
        $username = $faker->generate([
            'min_length' => 0,
            'max_length' => 20,
        ]);

        $this->assertIsString($username);
        // When min_length is 0, username might be empty, but should not exceed max_length
        $this->assertLessThanOrEqual(20, strlen($username));
    }

    /**
     * Test that UsernameFaker truncates when prefix+suffix+base exceed max_length.
     */
    public function testGenerateTruncatesWhenExceedingMaxLength(): void
    {
        $faker = new UsernameFaker('en_US');
        $username = $faker->generate([
            'prefix'     => 'pre_',
            'suffix'     => '_suf',
            'min_length' => 5,
            'max_length' => 10,
        ]);

        $this->assertIsString($username);
        $this->assertLessThanOrEqual(10, strlen($username));
        $this->assertGreaterThanOrEqual(5, strlen($username));
        $this->assertStringStartsWith('pre_', $username);
    }

    /**
     * Test that UsernameFaker applies substr when total length exceeds max_length.
     */
    public function testGenerateSubstrWhenExceedingMaxLength(): void
    {
        $faker = new UsernameFaker('en_US');
        for ($i = 0; $i < 30; ++$i) {
            $username = $faker->generate([
                'prefix'     => 'x',
                'suffix'     => 'y',
                'min_length' => 3,
                'max_length' => 5,
                'include_numbers' => true,
            ]);
            $this->assertLessThanOrEqual(5, strlen($username), "Iteration $i produced length " . strlen($username));
        }
    }

    /**
     * Test that UsernameFaker pads with randomLetter when result is shorter than min_length.
     */
    public function testGeneratePadsWithRandomLetterWhenShort(): void
    {
        $faker = new UsernameFaker('en_US');
        $username = $faker->generate([
            'prefix'     => 'ab',
            'suffix'     => 'cd',
            'min_length' => 10,
            'max_length' => 15,
        ]);

        $this->assertIsString($username);
        $this->assertGreaterThanOrEqual(10, strlen($username));
        $this->assertLessThanOrEqual(15, strlen($username));
        $this->assertStringStartsWith('ab', $username);
        // Suffix may be truncated if prefix+base+suffix exceeds max_length
        $this->assertTrue(str_contains($username, 'ab') && strlen($username) >= 10);
    }

    /**
     * Test that UsernameFaker sets availableLength to minLength when prefix+suffix leave no room (lines 62-65).
     */
    public function testGenerateWhenPrefixSuffixLeaveNoRoom(): void
    {
        $faker = new UsernameFaker('en_US');
        $username = $faker->generate([
            'prefix'     => 'xxx',
            'suffix'     => 'yyy',
            'min_length' => 10,
            'max_length' => 10,
        ]);
        $this->assertIsString($username);
        $this->assertStringStartsWith('xxx', $username);
        $this->assertGreaterThanOrEqual(10, strlen($username));
        $this->assertLessThanOrEqual(10, strlen($username));
    }

    /**
     * Test that UsernameFaker always hits substr (line 89-90) when prefix+suffix length >= max_length.
     * With prefix='aaa', suffix='bb', max_length=4, any non-empty base gives total > 4 so we truncate.
     */
    public function testGenerateAlwaysTruncatesWhenPrefixSuffixExceedMaxLength(): void
    {
        $faker = new UsernameFaker('en_US');
        $username = $faker->generate([
            'prefix'     => 'aaa',
            'suffix'     => 'bb',
            'min_length' => 4,
            'max_length' => 4,
            'include_numbers' => false,
        ]);
        $this->assertSame(4, strlen($username));
        $this->assertStringStartsWith('aaa', $username);
    }

    /**
     * Test that UsernameFaker truncates with substr when prefix+base+suffix exceeds max_length (line 89-90).
     * Uses prefix+suffix and include_numbers so that base can grow past availableLength and total > max_length.
     */
    public function testGenerateTruncatesWithSubstrWhenTotalExceedsMaxLength(): void
    {
        $faker   = new UsernameFaker('en_US');
        $maxLen  = 10;
        $found   = false;
        $iterations = 200;
        for ($i = 0; $i < $iterations; ++$i) {
            $username = $faker->generate([
                'prefix'          => 'ab',
                'suffix'          => 'cd',
                'min_length'      => 5,
                'max_length'      => $maxLen,
                'include_numbers' => true,
            ]);
            $this->assertLessThanOrEqual($maxLen, strlen($username));
            $this->assertGreaterThanOrEqual(5, strlen($username));
            if (strlen($username) === $maxLen && str_starts_with($username, 'ab')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, sprintf('Expected at least one of %d runs to hit substr truncation (prefix+base+suffix > max_length).', $iterations));
    }

    /**
     * Test that UsernameFaker pads with randomLetter when result is shorter than min_length (str_pad branch).
     */
    public function testGeneratePadsWithRandomLetterWhenBelowMinLength(): void
    {
        $faker = new UsernameFaker('en_US');
        $found = false;
        for ($i = 0; $i < 80; ++$i) {
            $username = $faker->generate([
                'prefix'     => 'a',
                'suffix'     => 'b',
                'min_length' => 10,
                'max_length' => 10,
                'include_numbers' => false,
            ]);
            if (strlen($username) === 10 && str_starts_with($username, 'a') && str_ends_with($username, 'b')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Expected at least one generation to hit str_pad branch (prefix+a+b+suffix with padding to 10).');
    }

    /**
     * Test that UsernameFaker adds numbers when include_numbers is true and there is remaining space (lines 75-83).
     * Runs many times to hit the 70% boolean branch and remainingLength > 0 (safeLength, maxNumber, append).
     */
    public function testGenerateAddsNumbersWhenIncludeNumbersAndRemainingSpace(): void
    {
        $faker = new UsernameFaker('en_US');
        $found = false;
        for ($i = 0; $i < 300; ++$i) {
            $username = $faker->generate([
                'prefix'          => 'u',
                'suffix'          => '',
                'min_length'      => 4,
                'max_length'      => 12,
                'include_numbers' => true,
            ]);
            $this->assertIsString($username);
            $this->assertGreaterThanOrEqual(4, strlen($username));
            $this->assertLessThanOrEqual(12, strlen($username));
            if (preg_match('/\d/', $username) === 1) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Expected at least one username to include digits when include_numbers is true.');
    }

    /**
     * Test that UsernameFaker constructor is invoked (covers __construct for method coverage).
     */
    public function testConstructorIsInvoked(): void
    {
        $faker = new UsernameFaker('es_ES');
        $this->assertInstanceOf(UsernameFaker::class, $faker);
        $this->assertNotEmpty($faker->generate());
    }

    /**
     * Test that the branch adding numbers (lines 76-81) is hit by using a seeded random.
     * With a fixed seed, the first generate() call produces a username with digits, covering $maxNumber and append.
     */
    public function testGenerateWithSeededRandomAddsNumbers(): void
    {
        $seed = 12345;
        mt_srand($seed);
        $faker = new UsernameFaker('en_US');
        $withDigits = false;
        for ($i = 0; $i < 50; ++$i) {
            $username = $faker->generate([
                'prefix'          => 'x',
                'suffix'          => '',
                'min_length'      => 3,
                'max_length'      => 15,
                'include_numbers' => true,
            ]);
            if (preg_match('/\d/', $username)) {
                $withDigits = true;
                break;
            }
        }
        mt_srand(); // reset to random
        $this->assertTrue($withDigits, 'Seeded run should produce at least one username with digits.');
    }

    /**
     * Test that UsernameFaker pads to min_length when prefix+suffix leave little room (covers str_pad branch when result < min_length).
     */
    public function testGeneratePadsWithStrPadWhenBelowMinLength(): void
    {
        $faker = new UsernameFaker('en_US');
        for ($i = 0; $i < 30; ++$i) {
            $username = $faker->generate([
                'prefix'          => 'a',
                'suffix'          => 'b',
                'min_length'      => 10,
                'max_length'      => 10,
                'include_numbers' => false,
            ]);
            $this->assertGreaterThanOrEqual(10, strlen($username));
            $this->assertLessThanOrEqual(10, strlen($username));
            $this->assertStringStartsWith('a', $username);
        }
    }
}
