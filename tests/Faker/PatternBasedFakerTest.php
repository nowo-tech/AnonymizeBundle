<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

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
     * Test that PatternBasedFaker uses fallback when source field is null.
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
            'fallback_faker' => 'username',
        ]);

        $this->assertIsString($result);
        $this->assertStringEndsWith('(15)', $result);
        $this->assertNotEquals('old@email.com(15)', $result); // Should use fallback, not original
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
}
