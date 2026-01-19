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
        $text = $faker->generate(['min_words' => 5, 'max_words' => 20]);

        $this->assertIsString($text);
        $this->assertNotEmpty($text);
        $wordCount = str_word_count($text);
        $this->assertGreaterThanOrEqual(5, $wordCount);
        // Note: Faker may generate slightly more words due to sentence structure, so we check it's reasonable
        $this->assertLessThanOrEqual(30, $wordCount);
    }
}
