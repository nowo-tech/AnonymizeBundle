<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Attribute;

use Nowo\AnonymizeBundle\Attribute\Anonymize;
use PHPUnit\Framework\TestCase;

/**
 * Test case for Anonymize attribute.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizeTest extends TestCase
{
    /**
     * Test that Anonymize attribute can be instantiated with default values.
     */
    public function testAnonymizeCanBeInstantiatedWithDefaults(): void
    {
        $attribute = new Anonymize();

        $this->assertNull($attribute->connection);
        $this->assertIsArray($attribute->includePatterns);
        $this->assertEmpty($attribute->includePatterns);
        $this->assertIsArray($attribute->excludePatterns);
        $this->assertEmpty($attribute->excludePatterns);
    }

    /**
     * Test that Anonymize attribute can be instantiated with connection.
     */
    public function testAnonymizeCanBeInstantiatedWithConnection(): void
    {
        $attribute = new Anonymize(connection: 'default');

        $this->assertEquals('default', $attribute->connection);
        $this->assertIsArray($attribute->includePatterns);
        $this->assertEmpty($attribute->includePatterns);
        $this->assertIsArray($attribute->excludePatterns);
        $this->assertEmpty($attribute->excludePatterns);
    }

    /**
     * Test that Anonymize attribute can be instantiated with include patterns.
     */
    public function testAnonymizeCanBeInstantiatedWithIncludePatterns(): void
    {
        $includePatterns = ['id' => '>100', 'status' => 'active'];
        $attribute = new Anonymize(includePatterns: $includePatterns);

        $this->assertNull($attribute->connection);
        $this->assertEquals($includePatterns, $attribute->includePatterns);
        $this->assertIsArray($attribute->excludePatterns);
        $this->assertEmpty($attribute->excludePatterns);
    }

    /**
     * Test that Anonymize attribute can be instantiated with exclude patterns.
     */
    public function testAnonymizeCanBeInstantiatedWithExcludePatterns(): void
    {
        $excludePatterns = ['id' => '<=100', 'deleted' => 'true'];
        $attribute = new Anonymize(excludePatterns: $excludePatterns);

        $this->assertNull($attribute->connection);
        $this->assertIsArray($attribute->includePatterns);
        $this->assertEmpty($attribute->includePatterns);
        $this->assertEquals($excludePatterns, $attribute->excludePatterns);
    }

    /**
     * Test that Anonymize attribute can be instantiated with all parameters.
     */
    public function testAnonymizeCanBeInstantiatedWithAllParameters(): void
    {
        $includePatterns = ['id' => '>100'];
        $excludePatterns = ['deleted' => 'true'];
        $attribute = new Anonymize(
            connection: 'custom',
            includePatterns: $includePatterns,
            excludePatterns: $excludePatterns
        );

        $this->assertEquals('custom', $attribute->connection);
        $this->assertEquals($includePatterns, $attribute->includePatterns);
        $this->assertEquals($excludePatterns, $attribute->excludePatterns);
    }

    /**
     * Test that Anonymize attribute properties are public and accessible.
     */
    public function testAnonymizePropertiesArePublic(): void
    {
        $attribute = new Anonymize(connection: 'test');

        $this->assertTrue(isset($attribute->connection));
        $this->assertTrue(isset($attribute->includePatterns));
        $this->assertTrue(isset($attribute->excludePatterns));

        $attribute->connection = 'modified';
        $this->assertEquals('modified', $attribute->connection);

        $attribute->includePatterns = ['new' => 'pattern'];
        $this->assertEquals(['new' => 'pattern'], $attribute->includePatterns);
    }
}
