<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Faker;

use Nowo\AnonymizeBundle\Faker\UtmFaker;
use PHPUnit\Framework\TestCase;

use function count;
use function strlen;

/**
 * Tests for UtmFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class UtmFakerTest extends TestCase
{
    private UtmFaker $faker;

    protected function setUp(): void
    {
        $this->faker = new UtmFaker();
    }

    public function testGenerateSource(): void
    {
        $result = $this->faker->generate(['type' => 'source']);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        // Source should be one of the common sources
        $commonSources = ['google', 'facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'newsletter', 'direct', 'referral', 'bing', 'yahoo', 'reddit', 'pinterest', 'tiktok', 'snapchat'];
        $this->assertContains($result, $commonSources);
    }

    public function testGenerateMedium(): void
    {
        $result = $this->faker->generate(['type' => 'medium']);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        // Medium should be one of the common mediums
        $commonMediums = ['cpc', 'cpm', 'email', 'social', 'organic', 'referral', 'affiliate', 'display', 'banner', 'retargeting', 'newsletter', 'sms', 'push', 'in-app', 'video', 'audio', 'print'];
        $this->assertContains($result, $commonMediums);
    }

    public function testGenerateCampaign(): void
    {
        $result = $this->faker->generate(['type' => 'campaign']);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertGreaterThanOrEqual(1, strlen($result));
    }

    public function testGenerateTerm(): void
    {
        $result = $this->faker->generate(['type' => 'term']);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertGreaterThanOrEqual(1, strlen($result));
    }

    public function testGenerateContent(): void
    {
        $result = $this->faker->generate(['type' => 'content']);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertGreaterThanOrEqual(1, strlen($result));
    }

    public function testGenerateDefaultIsSource(): void
    {
        $result = $this->faker->generate([]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        // Default should be source
        $commonSources = ['google', 'facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'newsletter', 'direct', 'referral', 'bing', 'yahoo', 'reddit', 'pinterest', 'tiktok', 'snapchat'];
        $this->assertContains($result, $commonSources);
    }

    public function testGenerateWithCustomSources(): void
    {
        $customSources = ['custom1', 'custom2', 'custom3'];
        $result        = $this->faker->generate([
            'type'           => 'source',
            'custom_sources' => $customSources,
        ]);

        $this->assertIsString($result);
        $this->assertContains($result, $customSources);
    }

    public function testGenerateWithCustomMediums(): void
    {
        $customMediums = ['custom1', 'custom2', 'custom3'];
        $result        = $this->faker->generate([
            'type'           => 'medium',
            'custom_mediums' => $customMediums,
        ]);

        $this->assertIsString($result);
        $this->assertContains($result, $customMediums);
    }

    public function testGenerateWithCustomCampaigns(): void
    {
        $customCampaigns = ['campaign1', 'campaign2', 'campaign3'];
        $result          = $this->faker->generate([
            'type'             => 'campaign',
            'custom_campaigns' => $customCampaigns,
        ]);

        $this->assertIsString($result);
        $this->assertContains($result, $customCampaigns);
    }

    public function testGenerateWithFormatSnakeCase(): void
    {
        $result = $this->faker->generate([
            'type'   => 'source',
            'format' => 'snake_case',
        ]);

        $this->assertIsString($result);
        $this->assertStringNotContainsString('-', $result);
    }

    public function testGenerateWithFormatKebabCase(): void
    {
        $result = $this->faker->generate([
            'type'   => 'source',
            'format' => 'kebab-case',
        ]);

        $this->assertIsString($result);
        // May or may not contain dashes depending on the source
    }

    public function testGenerateWithFormatCamelCase(): void
    {
        $result = $this->faker->generate([
            'type'   => 'source',
            'format' => 'camelCase',
        ]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test that UtmFaker toCamelCase is used when custom source contains underscore.
     */
    public function testGenerateWithCustomSourceUnderscoreAndCamelCase(): void
    {
        $result = $this->faker->generate([
            'type'           => 'source',
            'custom_sources' => ['my_custom_source'],
            'format'         => 'camelCase',
        ]);

        $this->assertIsString($result);
        $this->assertEquals('myCustomSource', $result);
    }

    /**
     * Test that UtmFaker toPascalCase is used when custom campaign contains underscore.
     */
    public function testGenerateWithCustomCampaignUnderscoreAndPascalCase(): void
    {
        $result = $this->faker->generate([
            'type'             => 'campaign',
            'custom_campaigns' => ['two_part_campaign'],
            'format'           => 'PascalCase',
        ]);

        $this->assertIsString($result);
        $this->assertEquals('TwoPartCampaign', $result);
    }

    public function testGenerateWithFormatLowercase(): void
    {
        $result = $this->faker->generate([
            'type'   => 'source',
            'format' => 'lowercase',
        ]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(strtolower($result), $result);
    }

    public function testGenerateWithFormatPascalCase(): void
    {
        $result = $this->faker->generate([
            'type'   => 'source',
            'format' => 'PascalCase',
        ]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateWithPrefix(): void
    {
        $prefix = 'pre_';
        $result = $this->faker->generate([
            'type'   => 'source',
            'prefix' => $prefix,
        ]);

        $this->assertStringStartsWith($prefix, $result);
    }

    public function testGenerateWithSuffix(): void
    {
        $suffix = '_suf';
        $result = $this->faker->generate([
            'type'   => 'source',
            'suffix' => $suffix,
        ]);

        $this->assertStringEndsWith($suffix, $result);
    }

    public function testGenerateWithPrefixAndSuffix(): void
    {
        $prefix = 'pre_';
        $suffix = '_suf';
        $result = $this->faker->generate([
            'type'   => 'source',
            'prefix' => $prefix,
            'suffix' => $suffix,
        ]);

        $this->assertStringStartsWith($prefix, $result);
        $this->assertStringEndsWith($suffix, $result);
    }

    public function testGenerateCampaignWithMinMaxLength(): void
    {
        $result = $this->faker->generate([
            'type'       => 'campaign',
            'min_length' => 10,
            'max_length' => 20,
        ]);

        $this->assertIsString($result);
        $this->assertGreaterThanOrEqual(10, strlen($result));
        $this->assertLessThanOrEqual(20, strlen($result));
    }

    public function testGenerateTermWithMinMaxLength(): void
    {
        $result = $this->faker->generate([
            'type'       => 'term',
            'min_length' => 5,
            'max_length' => 15,
        ]);

        $this->assertIsString($result);
        $this->assertGreaterThanOrEqual(5, strlen($result));
        $this->assertLessThanOrEqual(15, strlen($result));
    }

    public function testGenerateContentWithMinMaxLength(): void
    {
        $result = $this->faker->generate([
            'type'       => 'content',
            'min_length' => 8,
            'max_length' => 20,
        ]);

        $this->assertIsString($result);
        $this->assertGreaterThanOrEqual(8, strlen($result));
        $this->assertLessThanOrEqual(20, strlen($result));
    }

    public function testGenerateWithInvalidTypeDefaultsToSource(): void
    {
        $result = $this->faker->generate(['type' => 'invalid_type']);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        // Invalid type should default to source
        $commonSources = ['google', 'facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'newsletter', 'direct', 'referral', 'bing', 'yahoo', 'reddit', 'pinterest', 'tiktok', 'snapchat'];
        $this->assertContains($result, $commonSources);
    }

    /**
     * Test that UtmFaker uses default format branch for unknown format.
     */
    public function testGenerateWithUnknownFormat(): void
    {
        $result = $this->faker->generate([
            'type'   => 'source',
            'format' => 'unknown_format',
        ]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test source with camelCase format (covers toCamelCase with single-word value, no underscore).
     */
    public function testGenerateSourceWithCamelCaseFormat(): void
    {
        $result = $this->faker->generate(['type' => 'source', 'format' => 'camelCase']);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $commonSources = ['google', 'facebook', 'twitter', 'linkedin', 'instagram', 'youtube', 'newsletter', 'direct', 'referral', 'bing', 'yahoo', 'reddit', 'pinterest', 'tiktok', 'snapchat'];
        $this->assertContains(strtolower($result), $commonSources);
    }

    /**
     * Test source with PascalCase format (covers toPascalCase with single-word value).
     */
    public function testGenerateSourceWithPascalCaseFormat(): void
    {
        $result = $this->faker->generate(['type' => 'source', 'format' => 'PascalCase']);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertMatchesRegularExpression('/^[A-Z][a-z]+$/', $result, 'PascalCase source should be one capitalized word');
    }

    /**
     * Test campaign with camelCase format (covers toCamelCase with value containing underscore).
     */
    public function testGenerateCampaignWithCamelCaseFormat(): void
    {
        $found = false;
        for ($i = 0; $i < 50; ++$i) {
            $result = $this->faker->generate(['type' => 'campaign', 'format' => 'camelCase']);
            if (str_contains($result, '_') === false && strlen($result) >= 2) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Expected at least one campaign in camelCase without underscore');
    }

    public function testGenerateMultipleTimesReturnsDifferentValues(): void
    {
        $results = [];
        for ($i = 0; $i < 10; ++$i) {
            $results[] = $this->faker->generate(['type' => 'source']);
        }

        // Should have at least some variation (not all the same)
        $uniqueResults = array_unique($results);
        $this->assertGreaterThan(1, count($uniqueResults));
    }

    public function testGenerateCampaignMultipleTimes(): void
    {
        $results = [];
        for ($i = 0; $i < 10; ++$i) {
            $results[] = $this->faker->generate(['type' => 'campaign']);
        }

        // All should be strings
        foreach ($results as $result) {
            $this->assertIsString($result);
            $this->assertNotEmpty($result);
        }
    }

    public function testGenerateTermMultipleTimes(): void
    {
        $results = [];
        for ($i = 0; $i < 10; ++$i) {
            $results[] = $this->faker->generate(['type' => 'term']);
        }

        // All should be strings
        foreach ($results as $result) {
            $this->assertIsString($result);
            $this->assertNotEmpty($result);
        }
    }

    public function testGenerateContentMultipleTimes(): void
    {
        $results = [];
        for ($i = 0; $i < 10; ++$i) {
            $results[] = $this->faker->generate(['type' => 'content']);
        }

        // All should be strings
        foreach ($results as $result) {
            $this->assertIsString($result);
            $this->assertNotEmpty($result);
        }
    }

    /**
     * Test that UtmFaker pads value when shorter than min_length after format (e.g. lowercase).
     */
    public function testGenerateTermWithHighMinLengthPadsValue(): void
    {
        $result = $this->faker->generate([
            'type'       => 'term',
            'min_length' => 25,
            'max_length' => 35,
            'format'     => 'lowercase',
        ]);

        $this->assertIsString($result);
        $this->assertGreaterThanOrEqual(25, strlen($result));
        $this->assertLessThanOrEqual(35, strlen($result));
    }

    /**
     * Test that UtmFaker truncates when value exceeds max_length.
     */
    public function testGenerateCampaignTruncatesToMaxLength(): void
    {
        $result = $this->faker->generate([
            'type'       => 'campaign',
            'min_length' => 3,
            'max_length' => 8,
        ]);

        $this->assertIsString($result);
        $this->assertLessThanOrEqual(8, strlen($result));
        $this->assertGreaterThanOrEqual(3, strlen($result));
    }

    /**
     * Test that UtmFaker truncates value in generate() when length exceeds max_length after format (line 139).
     * Runs multiple times so that at least one campaign value is long enough to trigger substr truncation.
     */
    public function testGenerateTruncatesAfterFormatWhenExceedingMaxLength(): void
    {
        $maxLen = 4;
        $found  = false;
        for ($i = 0; $i < 50; ++$i) {
            $result = $this->faker->generate([
                'type'       => 'campaign',
                'min_length' => 2,
                'max_length' => $maxLen,
            ]);
            $this->assertIsString($result);
            $this->assertLessThanOrEqual($maxLen, strlen($result));
            if (strlen($result) === $maxLen) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'generate() should sometimes truncate campaign to max_length');
    }

    /**
     * Test that generate() applies substr when value (e.g. from custom_campaigns) exceeds max_length after format (line 139).
     */
    public function testGenerateTruncatesCustomCampaignToMaxLengthAfterFormat(): void
    {
        $longCampaign = 'very_long_campaign_name_that_exceeds_limit';
        $maxLen       = 5;
        $result       = $this->faker->generate([
            'type'             => 'campaign',
            'custom_campaigns' => [$longCampaign],
            'min_length'       => 2,
            'max_length'       => $maxLen,
        ]);
        $this->assertIsString($result);
        $this->assertLessThanOrEqual($maxLen, strlen($result));
        $this->assertSame($maxLen, strlen($result), 'custom long campaign should be truncated to max_length');
    }

    /**
     * Test that UtmFaker campaign with high min_length pads short pattern.
     */
    public function testGenerateCampaignWithHighMinLength(): void
    {
        $result = $this->faker->generate([
            'type'       => 'campaign',
            'min_length' => 25,
            'max_length' => 40,
        ]);

        $this->assertIsString($result);
        $this->assertGreaterThanOrEqual(25, strlen($result));
        $this->assertLessThanOrEqual(40, strlen($result));
    }

    /**
     * Test that UtmFaker content truncates when exceeding max_length.
     */
    public function testGenerateContentTruncatesToMaxLength(): void
    {
        $result = $this->faker->generate([
            'type'       => 'content',
            'min_length' => 2,
            'max_length' => 5,
        ]);

        $this->assertIsString($result);
        $this->assertLessThanOrEqual(5, strlen($result));
    }

    /**
     * Test that generate() pads value when shorter than min_length after format (lines 134-135).
     */
    public function testGeneratePadsWhenShorterThanMinLengthAfterFormat(): void
    {
        $minLen = 55;
        $found  = false;
        for ($i = 0; $i < 80; ++$i) {
            $result = $this->faker->generate([
                'type'       => 'content',
                'min_length' => $minLen,
                'max_length' => 65,
                'format'     => 'lowercase',
            ]);
            if (strlen($result) >= $minLen) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'generate() should pad content to min_length when needed');
    }

    /**
     * Test that generate() truncates when value exceeds max_length after format (line 139).
     */
    public function testGenerateTruncatesContentAfterFormatWhenOverMaxLength(): void
    {
        $maxLen = 5;
        $found  = false;
        for ($i = 0; $i < 60; ++$i) {
            $result = $this->faker->generate([
                'type'       => 'content',
                'min_length' => 2,
                'max_length' => $maxLen,
            ]);
            $this->assertLessThanOrEqual($maxLen, strlen($result));
            if (strlen($result) === $maxLen) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'generate() should sometimes truncate content to max_length');
    }

    /**
     * Test that generateCampaign pads pattern when shorter than min_length (lines 210-211).
     * Uses high min_length so predefined pattern (e.g. "rebate" 6 chars) triggers the while loop
     * that appends _word() until length >= min_length. Many iterations to hit the 70% pattern branch.
     */
    public function testGenerateCampaignPadsShortPattern(): void
    {
        $minLen = 25;
        $found  = false;
        for ($i = 0; $i < 250; ++$i) {
            $result = $this->faker->generate([
                'type'       => 'campaign',
                'min_length' => $minLen,
                'max_length' => 55,
            ]);
            $this->assertIsString($result);
            $this->assertGreaterThanOrEqual(1, strlen($result));
            $this->assertLessThanOrEqual(55, strlen($result));
            if (strlen($result) >= $minLen) {
                $found = true;
                if ($i > 10) {
                    break;
                }
            }
        }
        $this->assertTrue($found, 'generateCampaign should pad short pattern to min_length (while at 210-211)');
    }

    /**
     * Test that generateCampaign truncates pattern when longer than maxLength (substr in generateCampaign line 215).
     * Campaign patterns are 10-17 chars; with max_length 6 we force truncation.
     */
    public function testGenerateCampaignTruncatesLongPattern(): void
    {
        $maxLen = 6;
        $found  = false;
        for ($i = 0; $i < 60; ++$i) {
            $result = $this->faker->generate([
                'type'       => 'campaign',
                'min_length' => 3,
                'max_length' => $maxLen,
            ]);
            $this->assertLessThanOrEqual($maxLen, strlen($result));
            if (strlen($result) === $maxLen) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'generateCampaign should sometimes truncate long pattern to max_length');
    }

    /**
     * Test that UtmFaker term truncates when exceeding max_length.
     */
    public function testGenerateTermTruncatesToMaxLength(): void
    {
        $result = $this->faker->generate([
            'type'       => 'term',
            'min_length' => 2,
            'max_length' => 4,
        ]);

        $this->assertIsString($result);
        $this->assertLessThanOrEqual(4, strlen($result));
    }

    /**
     * Test that generateTerm truncates value inside the method when it exceeds max_length (covers line 266).
     * Using high min_length forces a long value, then max_length forces truncation before return; generate() then re-pads to min_length.
     */
    public function testGenerateTermTruncatesInsideMethodWhenExceedingMaxLength(): void
    {
        $result = $this->faker->generate([
            'type'       => 'term',
            'min_length' => 30,
            'max_length' => 10,
        ]);

        $this->assertIsString($result);
        $this->assertLessThanOrEqual(10, strlen($result));
        $this->assertGreaterThanOrEqual(1, strlen($result));
    }

    /**
     * Run campaign generation multiple times to hit random-words branch (when boolean(70) is false).
     */
    public function testGenerateCampaignRandomWordsBranch(): void
    {
        $results = [];
        for ($i = 0; $i < 50; ++$i) {
            $results[] = $this->faker->generate([
                'type'       => 'campaign',
                'min_length' => 8,
                'max_length' => 30,
            ]);
        }
        foreach ($results as $result) {
            $this->assertIsString($result);
            $this->assertGreaterThanOrEqual(8, strlen($result));
            $this->assertLessThanOrEqual(30, strlen($result));
        }
    }

    /**
     * Test that UtmFaker applies toCamelCase for term/campaign with multiple parts.
     */
    public function testGenerateTermWithCamelCaseFormat(): void
    {
        $result = $this->faker->generate([
            'type'       => 'term',
            'min_length' => 8,
            'max_length' => 30,
            'format'     => 'camelCase',
        ]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringNotContainsString('_', $result);
    }

    /**
     * Test that UtmFaker applies toPascalCase for campaign with multiple parts.
     */
    public function testGenerateCampaignWithPascalCaseFormat(): void
    {
        $result = $this->faker->generate([
            'type'       => 'campaign',
            'min_length' => 10,
            'max_length' => 35,
            'format'     => 'PascalCase',
        ]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test that generateCampaign random-words branch with very small max_length can produce a single word
     * (covers break in while loop and implode with words ?: [faker->word()] when words is empty).
     */
    public function testGenerateCampaignWithTinyMaxLengthUsesSingleWord(): void
    {
        $found = false;
        for ($i = 0; $i < 80; ++$i) {
            $result = $this->faker->generate([
                'type'       => 'campaign',
                'min_length' => 2,
                'max_length' => 6,
            ]);
            $this->assertIsString($result);
            $this->assertLessThanOrEqual(6, strlen($result));
            $this->assertGreaterThanOrEqual(2, strlen($result));
            if (strlen($result) >= 2 && strlen($result) <= 6) {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }
}
