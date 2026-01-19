<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Faker;

use Nowo\AnonymizeBundle\Faker\JsonFaker;
use PHPUnit\Framework\TestCase;

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
        $json = $faker->generate();

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
        $json = $faker->generate(['depth' => 1]);

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
        $json = $faker->generate(['max_items' => 3]);

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
        $faker = new JsonFaker('en_US');
        $schema = [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer'],
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
}
