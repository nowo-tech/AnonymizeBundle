<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Faker for generating anonymized UTM (Urchin Tracking Module) parameters.
 *
 * UTM parameters are used for tracking marketing campaigns in URLs:
 * - utm_source: The source of traffic (e.g., google, facebook, newsletter)
 * - utm_medium: The marketing medium (e.g., cpc, email, social, organic)
 * - utm_campaign: The campaign name (e.g., spring_sale, product_launch)
 * - utm_term: The search term (for paid search campaigns)
 * - utm_content: Specific content identifier (to differentiate links in the same campaign)
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
#[AsAlias(id: 'nowo_anonymize.faker.utm')]
final class UtmFaker implements FakerInterface
{
    private FakerGenerator $faker;

    public const SOURCE_TYPE = 'source';
    public const MEDIUM_TYPE = 'medium';
    public const CAMPAIGN_TYPE = 'campaign';
    public const TERM_TYPE = 'term';
    public const CONTENT_TYPE = 'content';

    public const SNAKE_CASE_FORMAT = 'snake_case';
    public const KEBAB_CASE_FORMAT = 'kebab-case';
    public const CAMEL_CASE_FORMAT = 'camelCase';
    public const LOWERCASE_FORMAT = 'lowercase';
    public const PASCAL_CASE_FORMAT = 'PascalCase';

    /**
     * Common UTM sources.
     *
     * @var array<string>
     */
    private const SOURCES = [
        'google', 'facebook', 'twitter', 'linkedin', 'instagram',
        'youtube', 'newsletter', 'direct', 'referral', 'bing',
        'yahoo', 'reddit', 'pinterest', 'tiktok', 'snapchat',
    ];

    /**
     * Common UTM mediums.
     *
     * @var array<string>
     */
    private const MEDIUMS = [
        'cpc', 'cpm', 'email', 'social', 'organic', 'referral',
        'affiliate', 'display', 'banner', 'retargeting', 'newsletter',
        'sms', 'push', 'in-app', 'video', 'audio', 'print',
    ];

    /**
     * Common campaign name patterns.
     *
     * @var array<string>
     */
    private const CAMPAIGN_PATTERNS = [
        'spring_sale', 'summer_promotion', 'winter_campaign', 'fall_offer',
        'product_launch', 'new_collection', 'limited_edition', 'flash_sale',
        'black_friday', 'cyber_monday', 'holiday_special', 'back_to_school',
        'anniversary', 'grand_opening', 'clearance', 'rebate',
    ];

    /**
     * Creates a new UtmFaker instance.
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
     * Generates an anonymized UTM parameter value.
     *
     * @param array<string, mixed> $options Options:
     *   - 'type' (string): The UTM parameter type - 'source', 'medium', 'campaign', 'term', or 'content' (default: 'source')
     *   - 'format' (string): Format style - 'snake_case', 'kebab-case', 'camelCase', or 'lowercase' (default: 'snake_case')
     *   - 'custom_sources' (array): Custom list of sources to use instead of defaults
     *   - 'custom_mediums' (array): Custom list of mediums to use instead of defaults
     *   - 'custom_campaigns' (array): Custom list of campaign patterns to use instead of defaults
     *   - 'prefix' (string): Optional prefix to add to the generated value
     *   - 'suffix' (string): Optional suffix to add to the generated value
     *   - 'min_length' (int): Minimum length for generated values (for campaign/term/content)
     *   - 'max_length' (int): Maximum length for generated values (for campaign/term/content)
     * @return string The anonymized UTM parameter value
     */
    public function generate(array $options = []): string
    {
        $type = $options['type'] ?? self::SOURCE_TYPE;
        $format = $options['format'] ?? self::SNAKE_CASE_FORMAT;
        $prefix = $options['prefix'] ?? '';
        $suffix = $options['suffix'] ?? '';

        $value = match ($type) {
            'source' => $this->generateSource($options),
            'medium' => $this->generateMedium($options),
            'campaign' => $this->generateCampaign($options),
            'term' => $this->generateTerm($options),
            'content' => $this->generateContent($options),
            default => $this->generateSource($options),
        };

        // Apply format
        $value = $this->applyFormat($value, $format);

        // Add prefix and suffix
        if ($prefix !== '') {
            $value = $prefix . $value;
        }
        if ($suffix !== '') {
            $value = $value . $suffix;
        }

        return $value;
    }

    /**
     * Generates a UTM source value.
     *
     * @param array<string, mixed> $options Options
     * @return string The generated source
     */
    private function generateSource(array $options): string
    {
        $sources = $options['custom_sources'] ?? self::SOURCES;
        return $this->faker->randomElement($sources);
    }

    /**
     * Generates a UTM medium value.
     *
     * @param array<string, mixed> $options Options
     * @return string The generated medium
     */
    private function generateMedium(array $options): string
    {
        $mediums = $options['custom_mediums'] ?? self::MEDIUMS;
        return $this->faker->randomElement($mediums);
    }

    /**
     * Generates a UTM campaign value.
     *
     * @param array<string, mixed> $options Options
     * @return string The generated campaign
     */
    private function generateCampaign(array $options): string
    {
        $customCampaigns = $options['custom_campaigns'] ?? null;
        if ($customCampaigns !== null && is_array($customCampaigns)) {
            return $this->faker->randomElement($customCampaigns);
        }

        // Generate a campaign name using patterns or random words
        $minLength = (int) ($options['min_length'] ?? 5);
        $maxLength = (int) ($options['max_length'] ?? 30);

        // Use predefined patterns or generate random
        if ($this->faker->boolean(70)) {
            // 70% chance to use a predefined pattern
            $pattern = $this->faker->randomElement(self::CAMPAIGN_PATTERNS);
            // Sometimes add a number or year
            if ($this->faker->boolean(40)) {
                $pattern .= '_' . $this->faker->numberBetween(2020, 2025);
            }
            return $pattern;
        }

        // Generate random campaign name
        $words = [];
        $currentLength = 0;
        $targetLength = $this->faker->numberBetween($minLength, $maxLength);

        while ($currentLength < $targetLength) {
            $word = $this->faker->word();
            if ($currentLength + strlen($word) + 1 <= $targetLength) {
                $words[] = $word;
                $currentLength += strlen($word) + 1;
            } else {
                break;
            }
        }

        return implode('_', $words ?: [$this->faker->word()]);
    }

    /**
     * Generates a UTM term value (search term).
     *
     * @param array<string, mixed> $options Options
     * @return string The generated term
     */
    private function generateTerm(array $options): string
    {
        $minLength = (int) ($options['min_length'] ?? 3);
        $maxLength = (int) ($options['max_length'] ?? 20);

        // Generate a search term (usually 1-3 words)
        $wordCount = $this->faker->numberBetween(1, 3);
        $words = [];
        $currentLength = 0;
        $targetLength = $this->faker->numberBetween($minLength, $maxLength);

        for ($i = 0; $i < $wordCount && $currentLength < $targetLength; $i++) {
            $word = $this->faker->word();
            if ($currentLength + strlen($word) + 1 <= $targetLength) {
                $words[] = $word;
                $currentLength += strlen($word) + 1;
            }
        }

        return implode('_', $words ?: [$this->faker->word()]);
    }

    /**
     * Generates a UTM content value.
     *
     * @param array<string, mixed> $options Options
     * @return string The generated content
     */
    private function generateContent(array $options): string
    {
        $minLength = (int) ($options['min_length'] ?? 5);
        $maxLength = (int) ($options['max_length'] ?? 25);

        // Generate content identifier (often includes position, variant, etc.)
        $patterns = [
            'link_' . $this->faker->numberBetween(1, 10),
            'button_' . $this->faker->randomElement(['top', 'bottom', 'middle', 'sidebar']),
            'banner_' . $this->faker->randomElement(['a', 'b', 'c', '1', '2', '3']),
            'image_' . $this->faker->numberBetween(1, 5),
            'text_' . $this->faker->numberBetween(1, 3),
            $this->faker->word() . '_' . $this->faker->numberBetween(1, 100),
        ];

        $value = $this->faker->randomElement($patterns);

        // Ensure it meets length requirements
        if (strlen($value) < $minLength) {
            $value .= '_' . $this->faker->word();
        }
        if (strlen($value) > $maxLength) {
            $value = substr($value, 0, $maxLength);
        }

        return $value;
    }

    /**
     * Applies the specified format to the value.
     *
     * @param string $value The value to format
     * @param string $format The format to apply
     * @return string The formatted value
     */
    private function applyFormat(string $value, string $format): string
    {
        return match ($format) {
            self::SNAKE_CASE_FORMAT => $value, // Already in snake_case
            self::KEBAB_CASE_FORMAT => str_replace('_', '-', $value),
            self::CAMEL_CASE_FORMAT => $this->toCamelCase($value),
            self::LOWERCASE_FORMAT => str_replace('_', '', strtolower($value)),
            self::PASCAL_CASE_FORMAT => $this->toPascalCase($value),
            default => $value,
        };
    }

    /**
     * Converts a string to camelCase.
     *
     * @param string $value The value to convert
     * @return string The camelCase value
     */
    private function toCamelCase(string $value): string
    {
        $parts = explode('_', $value);
        $first = array_shift($parts);
        $rest = array_map('ucfirst', $parts);
        return $first . implode('', $rest);
    }

    /**
     * Converts a string to PascalCase.
     *
     * @param string $value The value to convert
     * @return string The PascalCase value
     */
    private function toPascalCase(string $value): string
    {
        $parts = explode('_', $value);
        return implode('', array_map('ucfirst', $parts));
    }
}
