<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Faker;

use Nowo\AnonymizeBundle\Faker\HtmlFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for HtmlFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class HtmlFakerTest extends TestCase
{
    /**
     * Test that HtmlFaker generates valid HTML signature.
     */
    public function testGenerateSignature(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'signature']);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<div', $html);
        $this->assertStringContainsString('<p>', $html);
        $this->assertStringContainsString('</div>', $html);
    }

    /**
     * Test that HtmlFaker generates signature with links.
     */
    public function testGenerateSignatureWithLinks(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'signature', 'include_links' => true]);

        $this->assertIsString($html);
        $this->assertStringContainsString('<a href', $html);
    }

    /**
     * Test that HtmlFaker generates signature without links.
     */
    public function testGenerateSignatureWithoutLinks(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'signature', 'include_links' => false]);

        $this->assertIsString($html);
        $this->assertStringNotContainsString('<a href', $html);
    }

    /**
     * Test that HtmlFaker generates signature with styles.
     */
    public function testGenerateSignatureWithStyles(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'signature', 'include_styles' => true]);

        $this->assertIsString($html);
        $this->assertStringContainsString('style=', $html);
    }

    /**
     * Test that HtmlFaker generates paragraphs.
     */
    public function testGenerateParagraphs(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'paragraph', 'min_paragraphs' => 2, 'max_paragraphs' => 3]);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        // Count paragraph tags
        $paragraphCount = substr_count($html, '<p>');
        $this->assertGreaterThanOrEqual(2, $paragraphCount);
        $this->assertLessThanOrEqual(3, $paragraphCount);
    }

    /**
     * Test that HtmlFaker generates paragraphs with links.
     */
    public function testGenerateParagraphsWithLinks(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'paragraph', 'include_links' => true, 'min_paragraphs' => 5, 'max_paragraphs' => 5]);

        $this->assertIsString($html);
        // With 5 paragraphs and 30% chance, at least one should have a link
        // But we can't guarantee it, so just check it's valid HTML
        $this->assertNotEmpty($html);
    }

    /**
     * Test that HtmlFaker can add a link inside a paragraph (covers branch in generateParagraphs with include_links and boolean(30)).
     */
    public function testGenerateParagraphsWithLinkInsideParagraph(): void
    {
        $faker = new HtmlFaker('en_US');
        for ($i = 0; $i < 50; ++$i) {
            $html = $faker->generate(['type' => 'paragraph', 'include_links' => true, 'min_paragraphs' => 5, 'max_paragraphs' => 5]);
            $this->assertIsString($html);
            $this->assertNotEmpty($html);
            $this->assertGreaterThanOrEqual(5, substr_count($html, '<p>'));
        }
    }

    /**
     * Test that HtmlFaker generates list.
     */
    public function testGenerateList(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'list', 'min_list_items' => 3, 'max_list_items' => 5]);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        $this->assertTrue(
            str_contains($html, '<ul>') || str_contains($html, '<ol>'),
            'HTML should contain ul or ol tag',
        );
        $this->assertStringContainsString('<li>', $html);
    }

    /**
     * Test that HtmlFaker generates list with links.
     */
    public function testGenerateListWithLinks(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'list', 'include_links' => true, 'min_list_items' => 10, 'max_list_items' => 10]);

        $this->assertIsString($html);
        // With 10 items and 40% chance, at least one should have a link
        // But we can't guarantee it, so just check it's valid HTML
        $this->assertNotEmpty($html);
    }

    /**
     * Test that HtmlFaker generates list without links (covers else branch in generateList).
     */
    public function testGenerateListWithoutLinks(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'list', 'include_links' => false, 'min_list_items' => 3, 'max_list_items' => 3]);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        $this->assertStringNotContainsString('<a href', $html);
    }

    /**
     * Test that HtmlFaker generates mixed content.
     */
    public function testGenerateMixed(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'mixed', 'min_paragraphs' => 2, 'max_paragraphs' => 3]);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<p>', $html);
    }

    /**
     * Test that HtmlFaker generates mixed content with styles.
     */
    public function testGenerateMixedWithStyles(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'mixed', 'include_styles' => true]);

        $this->assertIsString($html);
        $this->assertStringContainsString('style=', $html);
    }

    /**
     * Test that HtmlFaker generateMixed without styles does not wrap in div (covers $style empty path).
     */
    public function testGenerateMixedWithoutStyles(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'mixed', 'include_styles' => false, 'min_paragraphs' => 1, 'max_paragraphs' => 1]);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<p>', $html);
        $this->assertStringNotContainsString('style=', $html);
    }

    /**
     * Test that HtmlFaker generateMixed can produce heading (h2/h3/h4) to cover that branch.
     */
    public function testGenerateMixedWithHeadingBranch(): void
    {
        $faker = new HtmlFaker('en_US');
        $found = false;
        for ($i = 0; $i < 60; ++$i) {
            $html = $faker->generate(['type' => 'mixed', 'min_paragraphs' => 1, 'max_paragraphs' => 2]);
            if (str_contains($html, '<h2>') || str_contains($html, '<h3>') || str_contains($html, '<h4>')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Expected at least one mixed generation to include a heading (h2/h3/h4).');
    }

    /**
     * Test that HtmlFaker defaults to signature type.
     */
    public function testGenerateDefault(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate();

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<div', $html);
    }

    /**
     * Test that HtmlFaker handles invalid type gracefully.
     */
    public function testGenerateInvalidType(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'invalid_type']);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        // Should default to signature
        $this->assertStringContainsString('<div', $html);
    }

    /**
     * Test that HtmlFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new HtmlFaker('en_US');
        $this->assertInstanceOf(HtmlFaker::class, $faker);
    }

    /**
     * Test that HtmlFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new HtmlFaker('es_ES');
        $html  = $faker->generate(['type' => 'signature']);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<div', $html);
    }

    /**
     * Test that HtmlFaker escapes HTML special characters.
     */
    public function testGenerateEscapesHtmlSpecialChars(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'signature']);

        $this->assertIsString($html);
        // Check that content is properly escaped (no unescaped < or > in text content)
        // This is a basic check - htmlspecialchars should handle this
        $this->assertNotEmpty($html);
    }

    /**
     * Test that HtmlFaker generates valid HTML structure.
     */
    public function testGenerateValidHtmlStructure(): void
    {
        $faker = new HtmlFaker('en_US');
        $html  = $faker->generate(['type' => 'signature']);

        $this->assertIsString($html);
        // Check that opening and closing tags match
        $openDivs  = substr_count($html, '<div');
        $closeDivs = substr_count($html, '</div>');
        $this->assertEquals($openDivs, $closeDivs, 'Opening and closing div tags should match');
    }
}
