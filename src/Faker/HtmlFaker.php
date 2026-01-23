<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized HTML content with lorem ipsum.
 *
 * Perfect for anonymizing email signatures, HTML templates, and other HTML content.
 * Generates valid HTML with lorem ipsum text while maintaining realistic structure.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.html')]
final class HtmlFaker implements FakerInterface
{
    private FakerGenerator $faker;

    /**
     * Creates a new HtmlFaker instance.
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
     * Generates anonymized HTML content with lorem ipsum.
     *
     * @param array<string, mixed> $options Options:
     *   - 'type' (string): 'signature', 'paragraph', 'list', or 'mixed' (default: 'signature')
     *   - 'include_links' (bool): Include hyperlinks in HTML (default: true)
     *   - 'include_styles' (bool): Include inline styles (default: false)
     *   - 'min_paragraphs' (int): Minimum number of paragraphs (default: 1)
     *   - 'max_paragraphs' (int): Maximum number of paragraphs (default: 3)
     *   - 'min_list_items' (int): Minimum number of list items (default: 2)
     *   - 'max_list_items' (int): Maximum number of list items (default: 5)
     * @return string The anonymized HTML content
     */
    public function generate(array $options = []): string
    {
        $type = $options['type'] ?? 'signature';
        $includeLinks = $options['include_links'] ?? true;
        $includeStyles = $options['include_styles'] ?? false;
        $minParagraphs = (int) ($options['min_paragraphs'] ?? 1);
        $maxParagraphs = (int) ($options['max_paragraphs'] ?? 3);
        $minListItems = (int) ($options['min_list_items'] ?? 2);
        $maxListItems = (int) ($options['max_list_items'] ?? 5);

        return match ($type) {
            'signature' => $this->generateSignature($includeLinks, $includeStyles),
            'paragraph' => $this->generateParagraphs($minParagraphs, $maxParagraphs, $includeLinks),
            'list' => $this->generateList($minListItems, $maxListItems, $includeLinks),
            'mixed' => $this->generateMixed($minParagraphs, $maxParagraphs, $minListItems, $maxListItems, $includeLinks, $includeStyles),
            default => $this->generateSignature($includeLinks, $includeStyles),
        };
    }

    /**
     * Generates an email signature-like HTML structure.
     *
     * @param bool $includeLinks Whether to include links
     * @param bool $includeStyles Whether to include inline styles
     * @return string The generated HTML signature
     */
    private function generateSignature(bool $includeLinks, bool $includeStyles): string
    {
        $name = $this->faker->name();
        $title = $this->faker->jobTitle();
        $company = $this->faker->company();
        $phone = $this->faker->phoneNumber();
        $email = $this->faker->email();
        $website = $this->faker->url();

        $style = $includeStyles ? ' style="font-family: Arial, sans-serif; font-size: 12px; color: #333;"' : '';
        $html = '<div' . $style . '>';

        // Name and title
        $html .= '<p><strong>' . htmlspecialchars($name) . '</strong><br>';
        $html .= htmlspecialchars($title) . '<br>';
        $html .= htmlspecialchars($company) . '</p>';

        // Contact information
        $html .= '<p>';
        if ($includeLinks) {
            $html .= 'Phone: <a href="tel:' . htmlspecialchars($phone) . '">' . htmlspecialchars($phone) . '</a><br>';
            $html .= 'Email: <a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a><br>';
            $html .= 'Website: <a href="' . htmlspecialchars($website) . '">' . htmlspecialchars(parse_url($website, PHP_URL_HOST) ?: $website) . '</a>';
        } else {
            $html .= 'Phone: ' . htmlspecialchars($phone) . '<br>';
            $html .= 'Email: ' . htmlspecialchars($email) . '<br>';
            $html .= 'Website: ' . htmlspecialchars($website);
        }
        $html .= '</p>';

        // Optional lorem ipsum paragraph
        if ($this->faker->boolean(70)) {
            $html .= '<p>' . htmlspecialchars($this->faker->paragraph(2)) . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generates HTML paragraphs with lorem ipsum.
     *
     * @param int $minParagraphs Minimum number of paragraphs
     * @param int $maxParagraphs Maximum number of paragraphs
     * @param bool $includeLinks Whether to include links
     * @return string The generated HTML paragraphs
     */
    private function generateParagraphs(int $minParagraphs, int $maxParagraphs, bool $includeLinks): string
    {
        $numParagraphs = $this->faker->numberBetween($minParagraphs, $maxParagraphs);
        $html = '';

        for ($i = 0; $i < $numParagraphs; $i++) {
            $text = $this->faker->paragraph(3);
            
            if ($includeLinks && $this->faker->boolean(30)) {
                // Add a link in the paragraph
                $words = explode(' ', $text);
                $linkIndex = $this->faker->numberBetween(0, count($words) - 1);
                $words[$linkIndex] = '<a href="' . htmlspecialchars($this->faker->url()) . '">' . $words[$linkIndex] . '</a>';
                $text = implode(' ', $words);
            }

            $html .= '<p>' . htmlspecialchars($text) . '</p>';
        }

        return $html;
    }

    /**
     * Generates an HTML list with lorem ipsum items.
     *
     * @param int $minItems Minimum number of list items
     * @param int $maxItems Maximum number of list items
     * @param bool $includeLinks Whether to include links
     * @return string The generated HTML list
     */
    private function generateList(int $minItems, int $maxItems, bool $includeLinks): string
    {
        $numItems = $this->faker->numberBetween($minItems, $maxItems);
        $listType = $this->faker->randomElement(['ul', 'ol']);
        $html = '<' . $listType . '>';

        for ($i = 0; $i < $numItems; $i++) {
            $text = $this->faker->sentence(4);
            
            if ($includeLinks && $this->faker->boolean(40)) {
                $text = '<a href="' . htmlspecialchars($this->faker->url()) . '">' . htmlspecialchars($text) . '</a>';
            } else {
                $text = htmlspecialchars($text);
            }

            $html .= '<li>' . $text . '</li>';
        }

        $html .= '</' . $listType . '>';

        return $html;
    }

    /**
     * Generates mixed HTML content (paragraphs, lists, etc.).
     *
     * @param int $minParagraphs Minimum number of paragraphs
     * @param int $maxParagraphs Maximum number of paragraphs
     * @param int $minListItems Minimum number of list items
     * @param int $maxListItems Maximum number of list items
     * @param bool $includeLinks Whether to include links
     * @param bool $includeStyles Whether to include inline styles
     * @return string The generated mixed HTML content
     */
    private function generateMixed(int $minParagraphs, int $maxParagraphs, int $minListItems, int $maxListItems, bool $includeLinks, bool $includeStyles): string
    {
        $html = '';
        $style = $includeStyles ? ' style="font-family: Arial, sans-serif;"' : '';
        
        if ($style) {
            $html .= '<div' . $style . '>';
        }

        // Add paragraphs
        $numParagraphs = $this->faker->numberBetween($minParagraphs, $maxParagraphs);
        for ($i = 0; $i < $numParagraphs; $i++) {
            $text = $this->faker->paragraph(3);
            $html .= '<p>' . htmlspecialchars($text) . '</p>';
        }

        // Optionally add a list
        if ($this->faker->boolean(60)) {
            $html .= $this->generateList($minListItems, $maxListItems, $includeLinks);
        }

        // Optionally add a heading
        if ($this->faker->boolean(40)) {
            $headingLevel = $this->faker->randomElement(['h2', 'h3', 'h4']);
            $html .= '<' . $headingLevel . '>' . htmlspecialchars($this->faker->sentence(3)) . '</' . $headingLevel . '>';
        }

        if ($style) {
            $html .= '</div>';
        }

        return $html;
    }
}
