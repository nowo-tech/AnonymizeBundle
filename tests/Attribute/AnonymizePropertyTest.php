<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Attribute;

use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use PHPUnit\Framework\TestCase;

/**
 * Test case for AnonymizeProperty attribute.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizePropertyTest extends TestCase
{
    /**
     * Test that AnonymizeProperty attribute can be instantiated with type only.
     */
    public function testAnonymizePropertyCanBeInstantiatedWithTypeOnly(): void
    {
        $attribute = new AnonymizeProperty(type: 'email');
        
        $this->assertEquals('email', $attribute->type);
        $this->assertNull($attribute->weight);
        $this->assertIsArray($attribute->includePatterns);
        $this->assertEmpty($attribute->includePatterns);
        $this->assertIsArray($attribute->excludePatterns);
        $this->assertEmpty($attribute->excludePatterns);
        $this->assertNull($attribute->service);
        $this->assertIsArray($attribute->options);
        $this->assertEmpty($attribute->options);
    }

    /**
     * Test that AnonymizeProperty attribute can be instantiated with weight.
     */
    public function testAnonymizePropertyCanBeInstantiatedWithWeight(): void
    {
        $attribute = new AnonymizeProperty(type: 'email', weight: 10);
        
        $this->assertEquals('email', $attribute->type);
        $this->assertEquals(10, $attribute->weight);
    }

    /**
     * Test that AnonymizeProperty attribute can be instantiated with include patterns.
     */
    public function testAnonymizePropertyCanBeInstantiatedWithIncludePatterns(): void
    {
        $includePatterns = ['id' => '>100', 'status' => 'active'];
        $attribute = new AnonymizeProperty(type: 'email', includePatterns: $includePatterns);
        
        $this->assertEquals('email', $attribute->type);
        $this->assertEquals($includePatterns, $attribute->includePatterns);
    }

    /**
     * Test that AnonymizeProperty attribute can be instantiated with exclude patterns.
     */
    public function testAnonymizePropertyCanBeInstantiatedWithExcludePatterns(): void
    {
        $excludePatterns = ['id' => '<=100', 'deleted' => 'true'];
        $attribute = new AnonymizeProperty(type: 'email', excludePatterns: $excludePatterns);
        
        $this->assertEquals('email', $attribute->type);
        $this->assertEquals($excludePatterns, $attribute->excludePatterns);
    }

    /**
     * Test that AnonymizeProperty attribute can be instantiated with service.
     */
    public function testAnonymizePropertyCanBeInstantiatedWithService(): void
    {
        $attribute = new AnonymizeProperty(type: 'service', service: 'custom_faker');
        
        $this->assertEquals('service', $attribute->type);
        $this->assertEquals('custom_faker', $attribute->service);
    }

    /**
     * Test that AnonymizeProperty attribute can be instantiated with options.
     */
    public function testAnonymizePropertyCanBeInstantiatedWithOptions(): void
    {
        $options = ['min' => 18, 'max' => 65];
        $attribute = new AnonymizeProperty(type: 'age', options: $options);
        
        $this->assertEquals('age', $attribute->type);
        $this->assertEquals($options, $attribute->options);
    }

    /**
     * Test that AnonymizeProperty attribute can be instantiated with all parameters.
     */
    public function testAnonymizePropertyCanBeInstantiatedWithAllParameters(): void
    {
        $includePatterns = ['id' => '>100'];
        $excludePatterns = ['deleted' => 'true'];
        $options = ['min' => 18, 'max' => 65];
        
        $attribute = new AnonymizeProperty(
            type: 'age',
            weight: 5,
            includePatterns: $includePatterns,
            excludePatterns: $excludePatterns,
            service: null,
            options: $options
        );
        
        $this->assertEquals('age', $attribute->type);
        $this->assertEquals(5, $attribute->weight);
        $this->assertEquals($includePatterns, $attribute->includePatterns);
        $this->assertEquals($excludePatterns, $attribute->excludePatterns);
        $this->assertNull($attribute->service);
        $this->assertEquals($options, $attribute->options);
    }

    /**
     * Test that AnonymizeProperty attribute properties are public and accessible.
     */
    public function testAnonymizePropertyPropertiesArePublic(): void
    {
        $attribute = new AnonymizeProperty(type: 'email');
        
        // Properties exist and are accessible
        $this->assertTrue(property_exists($attribute, 'type'));
        $this->assertTrue(property_exists($attribute, 'weight'));
        $this->assertTrue(property_exists($attribute, 'includePatterns'));
        $this->assertTrue(property_exists($attribute, 'excludePatterns'));
        $this->assertTrue(property_exists($attribute, 'service'));
        $this->assertTrue(property_exists($attribute, 'options'));
        
        // Properties can be read
        $this->assertEquals('email', $attribute->type);
        $this->assertNull($attribute->weight);
        $this->assertIsArray($attribute->includePatterns);
        $this->assertIsArray($attribute->excludePatterns);
        $this->assertNull($attribute->service);
        $this->assertIsArray($attribute->options);
        
        // Properties can be modified
        $attribute->type = 'name';
        $this->assertEquals('name', $attribute->type);
        
        $attribute->weight = 20;
        $this->assertEquals(20, $attribute->weight);
        
        $attribute->options = ['new' => 'option'];
        $this->assertEquals(['new' => 'option'], $attribute->options);
    }

    /**
     * Test that AnonymizeProperty attribute supports different faker types.
     */
    public function testAnonymizePropertySupportsDifferentFakerTypes(): void
    {
        $types = [
            'email', 'name', 'surname', 'age', 'phone', 'iban', 'credit_card',
            'address', 'date', 'username', 'url', 'company', 'masking', 'password',
            'ip_address', 'mac_address', 'uuid', 'hash', 'coordinate', 'color',
            'boolean', 'numeric', 'file', 'json', 'text', 'enum', 'country',
            'language', 'hash_preserve', 'shuffle', 'constant', 'service'
        ];
        
        foreach ($types as $type) {
            $attribute = new AnonymizeProperty(type: $type);
            $this->assertEquals($type, $attribute->type, "Failed for type: {$type}");
        }
    }
}
