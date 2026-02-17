<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

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
}
