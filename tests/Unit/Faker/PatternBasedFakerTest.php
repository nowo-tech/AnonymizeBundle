<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Unit\Faker;

use InvalidArgumentException;
use Nowo\AnonymizeBundle\Faker\PatternBasedFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for PatternBasedFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class PatternBasedFakerTest extends TestCase
{
    /**
     * Test that PatternBasedFaker generates value from source field with extracted pattern.
     */
    public function testGenerateFromSourceFieldWithPattern(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['email' => 'john@example.com'];
        $originalValue = 'hola@pepe.com(15)';

        $result = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record,
            'original_value' => $originalValue,
        ]);

        $this->assertIsString($result);
        $this->assertEquals('john@example.com(15)', $result);
    }

    /**
     * Test that PatternBasedFaker extracts pattern from original value.
     */
    public function testGenerateExtractsPattern(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['email' => 'jane@test.com'];
        $originalValue = 'user@domain.com(42)';

        $result = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record,
            'original_value' => $originalValue,
        ]);

        $this->assertIsString($result);
        $this->assertEquals('jane@test.com(42)', $result);
    }

    /**
     * Test that PatternBasedFaker works with custom pattern.
     */
    public function testGenerateWithCustomPattern(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['email' => 'test@example.com'];
        $originalValue = 'old@email.com-ID123';

        $result = $faker->generate([
            'source_field'        => 'email',
            'record'              => $record,
            'original_value'      => $originalValue,
            'pattern'             => '/-ID(\\d+)$/',  // Extract -ID123
            'pattern_replacement' => '-ID$1',  // Keep as -ID123
        ]);

        $this->assertIsString($result);
        $this->assertEquals('test@example.com-ID123', $result);
    }

    /**
     * Test that PatternBasedFaker works with separator.
     */
    public function testGenerateWithSeparator(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['email' => 'user@test.com'];
        $originalValue = 'old@email.com(99)';

        $result = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record,
            'original_value' => $originalValue,
            'separator'      => '_',
        ]);

        $this->assertIsString($result);
        $this->assertEquals('user@test.com_(99)', $result);
    }

    /**
     * Test that PatternBasedFaker finds source field when record key is lowercase (getFieldValue strtolower branch).
     */
    public function testGenerateFindsSourceFieldViaLowercaseKey(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['email' => 'anon@test.com'];
        $originalValue = 'old@email.com(15)';

        $result = $faker->generate([
            'source_field'   => 'Email',
            'record'         => $record,
            'original_value' => $originalValue,
        ]);

        $this->assertIsString($result);
        $this->assertEquals('anon@test.com(15)', $result);
    }

    /**
     * Test that PatternBasedFaker finds source field when record key is uppercase (getFieldValue strtoupper branch).
     */
    public function testGenerateFindsSourceFieldViaUppercaseKey(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['EMAIL' => 'up@test.com'];
        $originalValue = 'old@email.com(99)';

        $result = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record,
            'original_value' => $originalValue,
        ]);

        $this->assertIsString($result);
        $this->assertEquals('up@test.com(99)', $result);
    }

    /**
     * Test that PatternBasedFaker finds source field when record key is ucfirst (getFieldValue ucfirst branch).
     */
    public function testGenerateFindsSourceFieldViaUcfirstKey(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['Email' => 'uc@test.com'];
        $originalValue = 'old@email.com(42)';

        $result = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record,
            'original_value' => $originalValue,
        ]);

        $this->assertIsString($result);
        $this->assertEquals('uc@test.com(42)', $result);
    }

    /**
     * Test that PatternBasedFaker uses fallback when source field is empty string.
     * Uses 'uuid' fallback to avoid dependency on intl extension (email uses domainWord).
     */
    public function testGenerateWithFallbackWhenSourceFieldIsEmptyString(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['email' => ''];
        $originalValue = 'old@email.com(15)';

        $result = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record,
            'original_value' => $originalValue,
            'fallback_faker' => 'uuid',
        ]);

        $this->assertIsString($result);
        $this->assertStringEndsWith('(15)', $result);
        $this->assertNotEquals('(15)', $result); // Should use fallback, not just pattern
    }

    /**
     * Test that PatternBasedFaker uses fallback when source field is null.
     * Uses constant faker to avoid Faker locale/intl dependency (email uses domainWord which needs intl).
     */
    public function testGenerateWithFallbackWhenSourceIsNull(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['email' => null];
        $originalValue = 'old@email.com(15)';

        $result = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record,
            'original_value' => $originalValue,
            'fallback_faker' => 'constant',
            'fallback_options' => ['value' => 'fallback@test.local'],
        ]);

        $this->assertIsString($result);
        $this->assertStringEndsWith('(15)', $result);
        $this->assertSame('fallback@test.local(15)', $result);
    }

    /**
     * Test that PatternBasedFaker uses fallback when record does not contain source field (getFieldValue returns null).
     */
    public function testGenerateWithFallbackWhenSourceFieldMissingFromRecord(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['name' => 'John', 'other' => 'value']; // no 'email' key
        $originalValue = 'old@email.com(15)';

        $result = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record,
            'original_value' => $originalValue,
            'fallback_faker' => 'constant',
            'fallback_options' => ['value' => 'fallback@test.local'],
        ]);

        $this->assertIsString($result);
        $this->assertStringEndsWith('(15)', $result);
        $this->assertSame('fallback@test.local(15)', $result);
    }

    /**
     * Test that PatternBasedFaker throws exception when source_field is missing.
     */
    public function testGenerateThrowsExceptionWhenSourceFieldMissing(): void
    {
        $faker = new PatternBasedFaker('en_US');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PatternBasedFaker requires a "source_field" option.');

        $faker->generate([
            'record'         => ['email' => 'test@example.com'],
            'original_value' => 'old@email.com(15)',
        ]);
    }

    /**
     * Test that PatternBasedFaker throws exception when record is missing.
     */
    public function testGenerateThrowsExceptionWhenRecordMissing(): void
    {
        $faker = new PatternBasedFaker('en_US');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PatternBasedFaker requires a "record" option with the full database record.');

        $faker->generate([
            'source_field'   => 'email',
            'original_value' => 'old@email.com(15)',
        ]);
    }

    /**
     * Test that PatternBasedFaker handles empty pattern extraction.
     */
    public function testGenerateHandlesEmptyPattern(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['email' => 'test@example.com'];
        $originalValue = 'old@email.com'; // No pattern to extract

        $result = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record,
            'original_value' => $originalValue,
        ]);

        $this->assertIsString($result);
        $this->assertEquals('test@example.com', $result); // No pattern appended
    }

    /**
     * Test that PatternBasedFaker handles null original value.
     */
    public function testGenerateHandlesNullOriginalValue(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['email' => 'test@example.com'];
        $originalValue = null;

        $result = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record,
            'original_value' => $originalValue,
        ]);

        $this->assertIsString($result);
        $this->assertEquals('test@example.com', $result); // No pattern appended
    }

    /**
     * Test that PatternBasedFaker extractPattern returns empty when original_value is not a string.
     */
    public function testGenerateHandlesNonStringOriginalValue(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['email' => 'test@example.com'];
        $originalValue = 12345;

        $result = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record,
            'original_value' => $originalValue,
        ]);

        $this->assertIsString($result);
        $this->assertEquals('test@example.com', $result);
    }

    /**
     * Test that PatternBasedFaker works with different field name variations.
     */
    public function testGenerateWithDifferentFieldNameVariations(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $originalValue = 'old@email.com(15)';

        // Test lowercase
        $record1 = ['email' => 'test1@example.com'];
        $result1 = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record1,
            'original_value' => $originalValue,
        ]);
        $this->assertEquals('test1@example.com(15)', $result1);

        // Test uppercase
        $record2 = ['EMAIL' => 'test2@example.com'];
        $result2 = $faker->generate([
            'source_field'   => 'EMAIL',
            'record'         => $record2,
            'original_value' => $originalValue,
        ]);
        $this->assertEquals('test2@example.com(15)', $result2);

        // Test ucfirst: record key is 'Email', source_field is 'email'
        $record3 = ['Email' => 'test3@example.com'];
        $result3 = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record3,
            'original_value' => $originalValue,
        ]);
        $this->assertEquals('test3@example.com(15)', $result3);

        // Test strtolower: record key is 'email', source_field is 'Email'
        $record4 = ['email' => 'test4@example.com'];
        $result4 = $faker->generate([
            'source_field'   => 'Email',
            'record'         => $record4,
            'original_value' => $originalValue,
        ]);
        $this->assertEquals('test4@example.com(15)', $result4);
    }

    /**
     * Test that PatternBasedFaker returns empty extracted pattern when pattern does not match.
     */
    public function testGenerateWhenPatternDoesNotMatch(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['email' => 'new@example.com'];
        $originalValue = 'no-parentheses-here';

        $result = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record,
            'original_value' => $originalValue,
        ]);

        $this->assertIsString($result);
        $this->assertEquals('new@example.com', $result);
    }

    /**
     * Test that PatternBasedFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new PatternBasedFaker('en_US');
        $this->assertInstanceOf(PatternBasedFaker::class, $faker);
    }

    /**
     * Test that PatternBasedFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker         = new PatternBasedFaker('es_ES');
        $record        = ['email' => 'test@example.com'];
        $originalValue = 'old@email.com(15)';

        $result = $faker->generate([
            'source_field'   => 'email',
            'record'         => $record,
            'original_value' => $originalValue,
        ]);

        $this->assertIsString($result);
        $this->assertEquals('test@example.com(15)', $result);
    }

    /**
     * Test that PatternBasedFaker works with complex pattern extraction.
     */
    public function testGenerateWithComplexPattern(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['email' => 'new@test.com'];
        $originalValue = 'old@email.com-user-123';

        $result = $faker->generate([
            'source_field'        => 'email',
            'record'              => $record,
            'original_value'      => $originalValue,
            'pattern'             => '/-user-(\\d+)$/',  // Extract -user-123
            'pattern_replacement' => '-user-$1',  // Keep as -user-123
        ]);

        $this->assertIsString($result);
        $this->assertEquals('new@test.com-user-123', $result);
    }

    /**
     * Test that PatternBasedFaker replacement supports $0 (full match).
     */
    public function testGenerateWithReplacementUsingFullMatch(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['email' => 'anon@example.com'];
        $originalValue = 'old@mail.com(99)';

        $result = $faker->generate([
            'source_field'        => 'email',
            'record'              => $record,
            'original_value'      => $originalValue,
            'pattern'             => '/(\\(\\d+\\))$/',
            'pattern_replacement' => '$0',
        ]);

        $this->assertIsString($result);
        $this->assertEquals('anon@example.com(99)', $result);
    }

    /**
     * Test that PatternBasedFaker replacement supports multiple capture groups ($1, $2).
     */
    public function testGenerateWithMultipleCaptureGroups(): void
    {
        $faker         = new PatternBasedFaker('en_US');
        $record        = ['email' => 'new@test.com'];
        $originalValue = 'user@domain.com-id-123';

        $result = $faker->generate([
            'source_field'        => 'email',
            'record'              => $record,
            'original_value'      => $originalValue,
            'pattern'             => '/-([a-z]+)-(\\d+)$/',
            'pattern_replacement' => '-$1-$2',
        ]);

        $this->assertIsString($result);
        $this->assertEquals('new@test.com-id-123', $result);
    }
}
