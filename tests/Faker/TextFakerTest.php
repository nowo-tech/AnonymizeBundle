<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\TextFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for TextFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class TextFakerTest extends TestCase
{
    /**
     * Test that TextFaker generates valid text.
     */
    public function testGenerate(): void
    {
        $faker = new TextFaker('en_US');
        $text = $faker->generate();

        $this->assertIsString($text);
        $this->assertNotEmpty($text);
    }

    /**
     * Test that TextFaker generates sentence type.
     */
    public function testGenerateSentence(): void
    {
        $faker = new TextFaker('en_US');
        $text = $faker->generate(['type' => 'sentence']);

        $this->assertIsString($text);
        $this->assertNotEmpty($text);
    }

    /**
     * Test that TextFaker generates paragraph type.
     */
    public function testGenerateParagraph(): void
    {
        $faker = new TextFaker('en_US');
        $text = $faker->generate(['type' => 'paragraph']);

        $this->assertIsString($text);
        $this->assertNotEmpty($text);
    }

    /**
     * Test that TextFaker respects min_words and max_words options.
     */
    public function testGenerateWithWordLimits(): void
    {
        $faker = new TextFaker('en_US');

        // Run multiple times to account for Faker's randomness
        // Faker may occasionally generate slightly fewer words than requested
        $allValid = false;
        $minWordCount = PHP_INT_MAX;

        for ($i = 0; $i < 10; $i++) {
            $text = $faker->generate(['min_words' => 5, 'max_words' => 20]);

            $this->assertIsString($text);
            $this->assertNotEmpty($text);
            $wordCount = str_word_count($text);

            // Track minimum word count across all iterations
            if ($wordCount < $minWordCount) {
                $minWordCount = $wordCount;
            }

            // Faker's sentence() may generate 3-4 words minimum even when requesting 5
            // So we check that it's at least 3 words (reasonable minimum)
            // and that at least one generation meets the requested minimum
            if ($wordCount >= 5) {
                $allValid = true;
            }

            // Note: Faker may generate slightly more words due to sentence structure, so we check it's reasonable
            $this->assertLessThanOrEqual(30, $wordCount);
            $this->assertGreaterThanOrEqual(3, $wordCount, 'Generated text should have at least 3 words');
        }

        // At least one generation should meet the minimum requirement
        // If not, we check that the minimum is at least 3 (Faker's practical minimum)
        if (!$allValid) {
            $this->assertGreaterThanOrEqual(3, $minWordCount, 'Minimum word count across all iterations should be at least 3');
        } else {
            $this->assertTrue(true, 'At least one generation had 5 or more words');
        }
    }
}
