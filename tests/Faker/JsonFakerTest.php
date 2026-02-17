<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\JsonFaker;
use PHPUnit\Framework\TestCase;

use function count;

/**
 * Test case for JsonFaker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class JsonFakerTest extends TestCase
{
    /**
     * Test that JsonFaker generates valid JSON.
     */
    public function testGenerate(): void
    {
        $faker = new JsonFaker('en_US');
        $json  = $faker->generate();

        $this->assertIsString($json);
        $this->assertNotEmpty($json);
        $this->assertJson($json);
    }

    /**
     * Test that JsonFaker respects depth option.
     */
    public function testGenerateWithDepth(): void
    {
        $faker = new JsonFaker('en_US');
        $json  = $faker->generate(['depth' => 1]);

        $this->assertIsString($json);
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
    }

    /**
     * Test that JsonFaker respects max_items option.
     */
    public function testGenerateWithMaxItems(): void
    {
        $faker = new JsonFaker('en_US');
        $json  = $faker->generate(['max_items' => 3]);

        $this->assertIsString($json);
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertLessThanOrEqual(3, count($data));
    }

    /**
     * Test that JsonFaker respects schema option.
     */
    public function testGenerateWithSchema(): void
    {
        $faker  = new JsonFaker('en_US');
        $schema = [
            'name'   => ['type' => 'string'],
            'age'    => ['type' => 'integer'],
            'active' => ['type' => 'boolean'],
        ];
        $json = $faker->generate(['schema' => $schema]);

        $this->assertIsString($json);
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('age', $data);
        $this->assertArrayHasKey('active', $data);
    }

    /**
     * Test that JsonFaker handles nested schema.
     */
    public function testGenerateWithNestedSchema(): void
    {
        $faker  = new JsonFaker('en_US');
        $schema = [
            'user' => [
                'name' => ['type' => 'string'],
                'age'  => ['type' => 'integer'],
            ],
            'settings' => [
                'active' => ['type' => 'boolean'],
            ],
        ];
        $json = $faker->generate(['schema' => $schema, 'depth' => 3]);

        $this->assertIsString($json);
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('user', $data);
        $this->assertIsArray($data['user']);
    }

    /**
     * Test that JsonFaker handles schema with different types.
     */
    public function testGenerateWithSchemaTypes(): void
    {
        $faker  = new JsonFaker('en_US');
        $schema = [
            'string_field'  => ['type' => 'string'],
            'integer_field' => ['type' => 'integer'],
            'float_field'   => ['type' => 'float'],
            'boolean_field' => ['type' => 'boolean'],
            'array_field'   => ['type' => 'array'],
            'object_field'  => ['type' => 'object'],
        ];
        $json = $faker->generate(['schema' => $schema, 'depth' => 2]);

        $this->assertIsString($json);
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
    }

    /**
     * Test that JsonFaker handles schema with non-array values.
     */
    public function testGenerateWithSchemaNonArrayValues(): void
    {
        $faker  = new JsonFaker('en_US');
        $schema = [
            'simple_key'  => 'simple_value',
            'complex_key' => ['type' => 'string'],
        ];
        $json = $faker->generate(['schema' => $schema]);

        $this->assertIsString($json);
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
    }

    /**
     * Test that JsonFaker handles zero depth.
     */
    public function testGenerateWithZeroDepth(): void
    {
        $faker = new JsonFaker('en_US');
        $json  = $faker->generate(['depth' => 0]);

        $this->assertIsString($json);
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
    }

    /**
     * Test that JsonFaker handles schema with zero depth.
     */
    public function testGenerateWithSchemaZeroDepth(): void
    {
        $faker  = new JsonFaker('en_US');
        $schema = [
            'nested' => [
                'deep' => ['type' => 'string'],
            ],
        ];
        $json = $faker->generate(['schema' => $schema, 'depth' => 0]);

        $this->assertIsString($json);
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
    }

    /**
     * Test that JsonFaker handles invalid schema type gracefully.
     */
    public function testGenerateWithInvalidSchemaType(): void
    {
        $faker  = new JsonFaker('en_US');
        $schema = [
            'field' => ['type' => 'invalid_type'],
        ];
        $json = $faker->generate(['schema' => $schema]);

        $this->assertIsString($json);
        $this->assertJson($json);
    }

    /**
     * Test that JsonFaker constructor works.
     */
    public function testConstructor(): void
    {
        $faker = new JsonFaker('en_US');
        $this->assertInstanceOf(JsonFaker::class, $faker);
    }

    /**
     * Test that JsonFaker works with different locales.
     */
    public function testGenerateWithDifferentLocale(): void
    {
        $faker = new JsonFaker('es_ES');
        $json  = $faker->generate();

        $this->assertIsString($json);
        $this->assertJson($json);
    }

    /**
     * Test that JsonFaker handles schema with nested arrays without type.
     */
    public function testGenerateWithNestedArraysWithoutType(): void
    {
        $faker  = new JsonFaker('en_US');
        $schema = [
            'level1' => [
                'level2' => [
                    'level3' => 'value',
                ],
            ],
        ];
        $json = $faker->generate(['schema' => $schema, 'depth' => 3]);

        $this->assertIsString($json);
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('level1', $data);
    }

    /**
     * Test that JsonFaker handles object type with zero maxDepth.
     */
    public function testGenerateWithObjectTypeZeroDepth(): void
    {
        $faker  = new JsonFaker('en_US');
        $schema = [
            'object_field' => ['type' => 'object'],
        ];
        $json = $faker->generate(['schema' => $schema, 'depth' => 1]);

        $this->assertIsString($json);
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('object_field', $data);
        // When maxDepth is 0 in generateValueByType, object type should return empty array
        // But we need depth > 0 to actually process the schema
        $this->assertIsArray($data['object_field']);
    }

    /**
     * Test that JsonFaker handles number type (alias for integer).
     */
    public function testGenerateWithNumberType(): void
    {
        $faker  = new JsonFaker('en_US');
        $schema = [
            'number_field' => ['type' => 'number'],
        ];
        $json = $faker->generate(['schema' => $schema]);

        $this->assertIsString($json);
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('number_field', $data);
        $this->assertIsInt($data['number_field']);
    }

    /**
     * Test that JsonFaker handles generateRandomStructure with zero depth.
     */
    public function testGenerateRandomStructureWithZeroDepth(): void
    {
        $faker = new JsonFaker('en_US');
        $json  = $faker->generate(['depth' => 0, 'max_items' => 5]);

        $this->assertIsString($json);
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        // With zero depth, generateRandomStructure returns ['value' => word]
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('value', $data);
    }

    /**
     * Test that JsonFaker handles generateRandomStructure with negative depth.
     */
    public function testGenerateRandomStructureWithNegativeDepth(): void
    {
        $faker = new JsonFaker('en_US');
        $json  = $faker->generate(['depth' => -1, 'max_items' => 5]);

        $this->assertIsString($json);
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        // With negative depth, should return a simple structure
        $this->assertNotEmpty($data);
    }
}
