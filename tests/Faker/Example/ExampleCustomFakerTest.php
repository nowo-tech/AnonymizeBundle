<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker\Example;

use Doctrine\ORM\EntityManagerInterface;
use Nowo\AnonymizeBundle\Faker\Example\ExampleCustomFaker;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ExampleCustomFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class ExampleCustomFakerTest extends TestCase
{
    private ExampleCustomFaker $faker;

    protected function setUp(): void
    {
        $this->faker = new ExampleCustomFaker();
    }

    public function testGeneratePreservesOriginalWhenOptionIsTrue(): void
    {
        $originalValue = 'original_value';
        $options       = [
            'original_value'    => $originalValue,
            'preserve_original' => true,
            'record'            => [],
        ];

        $result = $this->faker->generate($options);

        $this->assertSame($originalValue, $result);
    }

    public function testGenerateAnonymizesWhenPreserveOriginalIsFalse(): void
    {
        $originalValue = 'sensitive_data';
        $options       = [
            'original_value'    => $originalValue,
            'preserve_original' => false,
            'record'            => [],
        ];

        $result = $this->faker->generate($options);

        $this->assertNotSame($originalValue, $result);
        $this->assertStringStartsWith('ANONYMIZED_', $result);
    }

    public function testGenerateAnonymizesWhenPreserveOriginalIsNotSet(): void
    {
        $originalValue = 'sensitive_data';
        $options       = [
            'original_value' => $originalValue,
            'record'         => [],
        ];

        $result = $this->faker->generate($options);

        $this->assertNotSame($originalValue, $result);
        $this->assertStringStartsWith('ANONYMIZED_', $result);
    }

    public function testGenerateReturnsNullWhenOriginalValueIsNull(): void
    {
        $options = [
            'original_value'    => null,
            'preserve_original' => false,
            'record'            => [],
        ];

        $result = $this->faker->generate($options);

        $this->assertNull($result);
    }

    public function testGenerateCanAccessRecordFields(): void
    {
        $originalValue = 'test_value';
        $options       = [
            'original_value'    => $originalValue,
            'preserve_original' => false,
            'record'            => [
                'other_field'       => 'other_value',
                'related_entity_id' => 123,
            ],
        ];

        // The faker should be able to access record fields
        // This test verifies the faker doesn't crash when accessing record
        $result = $this->faker->generate($options);

        $this->assertNotNull($result);
        $this->assertStringStartsWith('ANONYMIZED_', $result);
    }

    public function testGenerateHandlesCustomOptions(): void
    {
        $originalValue = 'test_value';
        $options       = [
            'original_value'    => $originalValue,
            'preserve_original' => false,
            'custom_option'     => 'custom_value',
            'record'            => [],
        ];

        $result = $this->faker->generate($options);

        $this->assertNotNull($result);
        $this->assertStringStartsWith('ANONYMIZED_', $result);
    }

    public function testGenerateWithEntityManager(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $faker         = new ExampleCustomFaker($entityManager);

        $originalValue = 'test_value';
        $options       = [
            'original_value'    => $originalValue,
            'preserve_original' => false,
            'related_entity'    => 'App\Entity\RelatedEntity',
            'record'            => [
                'related_entity_id' => 123,
            ],
        ];

        // Mock repository to return null (entity not found)
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('App\Entity\RelatedEntity')
            ->willReturn($this->createMock(\Doctrine\ORM\EntityRepository::class));

        $result = $faker->generate($options);

        $this->assertNotNull($result);
        $this->assertStringStartsWith('ANONYMIZED_', $result);
    }

    public function testGenerateWithEmptyRecord(): void
    {
        $originalValue = 'test_value';
        $options       = [
            'original_value'    => $originalValue,
            'preserve_original' => false,
            'record'            => [],
        ];

        $result = $this->faker->generate($options);

        $this->assertNotNull($result);
        $this->assertStringStartsWith('ANONYMIZED_', $result);
    }

    public function testGenerateWithNonStringValue(): void
    {
        $originalValue = 12345;
        $options       = [
            'original_value'    => $originalValue,
            'preserve_original' => false,
            'record'            => [],
        ];

        $result = $this->faker->generate($options);

        // For non-string values, should return default
        $this->assertSame('ANONYMIZED_VALUE', $result);
    }

    public function testGenerateWithBooleanValue(): void
    {
        $originalValue = true;
        $options       = [
            'original_value'    => $originalValue,
            'preserve_original' => false,
            'record'            => [],
        ];

        $result = $this->faker->generate($options);

        // For non-string values, should return default
        $this->assertSame('ANONYMIZED_VALUE', $result);
    }

    public function testGeneratePreservesOriginalWithNullRecord(): void
    {
        $originalValue = 'test_value';
        $options       = [
            'original_value'    => $originalValue,
            'preserve_original' => true,
        ];

        $result = $this->faker->generate($options);

        $this->assertSame($originalValue, $result);
    }

    public function testGenerateHandlesLongStrings(): void
    {
        $originalValue = str_repeat('a', 1000);
        $options       = [
            'original_value'    => $originalValue,
            'preserve_original' => false,
            'record'            => [],
        ];

        $result = $this->faker->generate($options);

        $this->assertNotSame($originalValue, $result);
        $this->assertStringStartsWith('ANONYMIZED_', $result);
    }

    public function testGenerateHandlesSpecialCharacters(): void
    {
        $originalValue = 'test with émojis 🎉 and unicode ñ';
        $options       = [
            'original_value'    => $originalValue,
            'preserve_original' => false,
            'record'            => [],
        ];

        $result = $this->faker->generate($options);

        $this->assertNotSame($originalValue, $result);
        $this->assertStringStartsWith('ANONYMIZED_', $result);
    }
}
