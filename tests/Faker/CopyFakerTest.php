<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\CopyFaker;
use PHPUnit\Framework\TestCase;

/**
 * Test case for CopyFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class CopyFakerTest extends TestCase
{
    /**
     * Test that CopyFaker copies value from source field.
     */
    public function testGenerateCopiesFromSourceField(): void
    {
        $faker = new CopyFaker();
        $record = ['email' => 'john@example.com'];

        $result = $faker->generate([
            'source_field' => 'email',
            'record' => $record,
        ]);

        $this->assertEquals('john@example.com', $result);
    }

    /**
     * Test that CopyFaker copies different value types.
     */
    public function testGenerateCopiesDifferentTypes(): void
    {
        $faker = new CopyFaker();

        // String
        $record1 = ['name' => 'John Doe'];
        $result1 = $faker->generate(['source_field' => 'name', 'record' => $record1]);
        $this->assertEquals('John Doe', $result1);

        // Integer
        $record2 = ['age' => 25];
        $result2 = $faker->generate(['source_field' => 'age', 'record' => $record2]);
        $this->assertEquals(25, $result2);

        // Boolean
        $record3 = ['active' => true];
        $result3 = $faker->generate(['source_field' => 'active', 'record' => $record3]);
        $this->assertTrue($result3);
    }

    /**
     * Test that CopyFaker uses fallback when source field is null.
     */
    public function testGenerateUsesFallbackWhenSourceIsNull(): void
    {
        $faker = new CopyFaker();
        $record = ['email' => null];

        $result = $faker->generate([
            'source_field' => 'email',
            'record' => $record,
            'fallback_faker' => 'email',
        ]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('@', $result); // Should be a valid email
    }

    /**
     * Test that CopyFaker uses fallback when source field is empty string.
     */
    public function testGenerateUsesFallbackWhenSourceIsEmpty(): void
    {
        $faker = new CopyFaker();
        $record = ['email' => ''];

        $result = $faker->generate([
            'source_field' => 'email',
            'record' => $record,
            'fallback_faker' => 'email',
        ]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test that CopyFaker throws exception when source_field is missing.
     */
    public function testGenerateThrowsExceptionWhenSourceFieldMissing(): void
    {
        $faker = new CopyFaker();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CopyFaker requires a "source_field" option.');

        $faker->generate([
            'record' => ['email' => 'test@example.com'],
        ]);
    }

    /**
     * Test that CopyFaker throws exception when record is missing.
     */
    public function testGenerateThrowsExceptionWhenRecordMissing(): void
    {
        $faker = new CopyFaker();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CopyFaker requires a "record" option with the full database record.');

        $faker->generate([
            'source_field' => 'email',
        ]);
    }

    /**
     * Test that CopyFaker works with different field name variations.
     */
    public function testGenerateWithDifferentFieldNameVariations(): void
    {
        $faker = new CopyFaker();

        // Test lowercase
        $record1 = ['email' => 'test1@example.com'];
        $result1 = $faker->generate(['source_field' => 'email', 'record' => $record1]);
        $this->assertEquals('test1@example.com', $result1);

        // Test uppercase
        $record2 = ['EMAIL' => 'test2@example.com'];
        $result2 = $faker->generate(['source_field' => 'EMAIL', 'record' => $record2]);
        $this->assertEquals('test2@example.com', $result2);

        // Test ucfirst
        $record3 = ['Email' => 'test3@example.com'];
        $result3 = $faker->generate(['source_field' => 'Email', 'record' => $record3]);
        $this->assertEquals('test3@example.com', $result3);
    }

    /**
     * Test that CopyFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new CopyFaker();
        $this->assertInstanceOf(CopyFaker::class, $faker);
    }

    /**
     * Test that CopyFaker works with custom fallback options.
     */
    public function testGenerateWithCustomFallbackOptions(): void
    {
        $faker = new CopyFaker();
        $record = ['email' => null];

        $result = $faker->generate([
            'source_field' => 'email',
            'record' => $record,
            'fallback_faker' => 'name',
            'fallback_options' => ['gender' => 'male'],
        ]);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }
}
