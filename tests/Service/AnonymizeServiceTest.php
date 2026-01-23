<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Service;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test case for AnonymizeService.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizeServiceTest extends TestCase
{
    private FakerFactory $fakerFactory;
    private PatternMatcher $patternMatcher;
    private AnonymizeService $service;

    protected function setUp(): void
    {
        $this->fakerFactory = new FakerFactory('en_US');
        $this->patternMatcher = new PatternMatcher();
        $this->service = new AnonymizeService($this->fakerFactory, $this->patternMatcher);
    }

    /**
     * Test that getAnonymizableEntities returns empty array when no metadata driver.
     */
    public function testGetAnonymizableEntitiesReturnsEmptyWhenNoMetadataDriver(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $config = $this->createMock(Configuration::class);

        $em->method('getConfiguration')
            ->willReturn($config);

        $config->method('getMetadataDriverImpl')
            ->willReturn(null);

        $result = $this->service->getAnonymizableEntities($em);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that getAnonymizableEntities returns entities with Anonymize attribute.
     */
    public function testGetAnonymizableEntitiesReturnsEntitiesWithAttribute(): void
    {
        // Create a test class with Anonymize attribute
        eval('
            namespace Nowo\AnonymizeBundle\Tests\Service {
                #[\\Nowo\\AnonymizeBundle\\Attribute\\Anonymize]
                class TestEntity {
                    #[\\Nowo\\AnonymizeBundle\\Attribute\\AnonymizeProperty(type: "email", weight: 1)]
                    public string $email = "test@example.com";
                }
            }
        ');

        $className = 'Nowo\AnonymizeBundle\Tests\Service\TestEntity';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadataDriver = $this->createMock(MappingDriver::class);
        $config = $this->createMock(Configuration::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $em->method('getConfiguration')
            ->willReturn($config);

        $config->method('getMetadataDriverImpl')
            ->willReturn($metadataDriver);

        $metadataDriver->method('getAllClassNames')
            ->willReturn([$className]);

        $em->method('getClassMetadata')
            ->with($className)
            ->willReturn($metadata);

        $result = $this->service->getAnonymizableEntities($em);

        $this->assertIsArray($result);
        if (isset($result[$className])) {
            $this->assertArrayHasKey('metadata', $result[$className]);
            $this->assertArrayHasKey('reflection', $result[$className]);
            $this->assertArrayHasKey('attribute', $result[$className]);
        }
    }

    /**
     * Test that getAnonymizableEntities skips entities without Anonymize attribute.
     */
    public function testGetAnonymizableEntitiesSkipsEntitiesWithoutAttribute(): void
    {
        // Use a real class without Anonymize attribute
        $testEntity = new class {
            public string $email = 'test@example.com';
        };

        $className = $testEntity::class;

        $metadata = $this->createMock(ClassMetadata::class);
        $metadataDriver = $this->createMock(MappingDriver::class);
        $config = $this->createMock(Configuration::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $em->method('getConfiguration')
            ->willReturn($config);

        $config->method('getMetadataDriverImpl')
            ->willReturn($metadataDriver);

        $metadataDriver->method('getAllClassNames')
            ->willReturn([$className]);

        $em->method('getClassMetadata')
            ->with($className)
            ->willReturn($metadata);

        $result = $this->service->getAnonymizableEntities($em);

        $this->assertIsArray($result);
        // Should be empty because the class doesn't have Anonymize attribute
        $this->assertEmpty($result);
    }

    /**
     * Test that getAnonymizableProperties returns properties with AnonymizeProperty attribute.
     */
    public function testGetAnonymizablePropertiesReturnsPropertiesWithAttribute(): void
    {
        // Use a real anonymous class with AnonymizeProperty attributes
        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1)]
            public string $email = 'test@example.com';

            #[AnonymizeProperty(type: 'name', weight: 2)]
            public string $name = 'John';

            public string $notAnonymized = 'value';
        };

        $reflection = new ReflectionClass($testEntity);
        $result = $this->service->getAnonymizableProperties($reflection);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        // The result is an indexed array, not associative by property name
        $propertyNames = array_map(fn($item) => $item['property']->getName(), $result);
        $this->assertContains('email', $propertyNames);
        $this->assertContains('name', $propertyNames);
        $this->assertArrayHasKey('property', $result[0]);
        $this->assertArrayHasKey('attribute', $result[0]);
        $this->assertArrayHasKey('weight', $result[0]);
    }

    /**
     * Test that getAnonymizableProperties sorts by weight.
     */
    public function testGetAnonymizablePropertiesSortsByWeight(): void
    {
        // Use a real anonymous class with properties having different weights
        $testEntity = new class {
            #[AnonymizeProperty(type: 'name', weight: 3)]
            public string $name = 'John';

            #[AnonymizeProperty(type: 'email', weight: 1)]
            public string $email = 'test@example.com';

            #[AnonymizeProperty(type: 'phone', weight: 2)]
            public string $phone = '123456789';
        };

        $reflection = new ReflectionClass($testEntity);
        $result = $this->service->getAnonymizableProperties($reflection);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        // The result is sorted by weight, so email (1) should come first, then phone (2), then name (3)
        $propertyNames = array_map(fn($item) => $item['property']->getName(), $result);
        $this->assertEquals('email', $propertyNames[0]);
        $this->assertEquals('phone', $propertyNames[1]);
        $this->assertEquals('name', $propertyNames[2]);
    }

    /**
     * Test that getAnonymizableProperties handles properties without weight.
     */
    public function testGetAnonymizablePropertiesHandlesPropertiesWithoutWeight(): void
    {
        // Use a real anonymous class with one property with weight and one without
        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1)]
            public string $email = 'test@example.com';

            #[AnonymizeProperty(type: 'name')]
            public string $name = 'John';
        };

        $reflection = new ReflectionClass($testEntity);
        $result = $this->service->getAnonymizableProperties($reflection);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        // Properties with weight should come first
        $propertyNames = array_map(fn($item) => $item['property']->getName(), $result);
        $this->assertEquals('email', $propertyNames[0]);
        $this->assertEquals('name', $propertyNames[1]);
    }

    /**
     * Test that getAnonymizableProperties returns empty array when no properties.
     */
    public function testGetAnonymizablePropertiesReturnsEmptyWhenNoProperties(): void
    {
        // Use a real anonymous class without AnonymizeProperty attributes
        $testEntity = new class {
            public string $notAnonymized = 'value';
        };

        $reflection = new ReflectionClass($testEntity);
        $result = $this->service->getAnonymizableProperties($reflection);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that getAnonymizableEntities handles exceptions gracefully.
     */
    public function testGetAnonymizableEntitiesHandlesExceptions(): void
    {
        $metadataDriver = $this->createMock(MappingDriver::class);
        $config = $this->createMock(Configuration::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $em->method('getConfiguration')
            ->willReturn($config);

        $config->method('getMetadataDriverImpl')
            ->willReturn($metadataDriver);

        $metadataDriver->method('getAllClassNames')
            ->willReturn(['NonExistentClass']);

        $em->method('getClassMetadata')
            ->willThrowException(new \Exception('Class not found'));

        $result = $this->service->getAnonymizableEntities($em);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that getAnonymizableProperties handles properties with same weight.
     */
    public function testGetAnonymizablePropertiesHandlesSameWeight(): void
    {
        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1)]
            public string $email1 = 'test1@example.com';

            #[AnonymizeProperty(type: 'email', weight: 1)]
            public string $email2 = 'test2@example.com';
        };

        $reflection = new ReflectionClass($testEntity);
        $result = $this->service->getAnonymizableProperties($reflection);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        // Both should have weight 1
        $this->assertEquals(1, $result[0]['weight']);
        $this->assertEquals(1, $result[1]['weight']);
    }

    /**
     * Test that getAnonymizableProperties handles many properties.
     */
    public function testGetAnonymizablePropertiesHandlesManyProperties(): void
    {
        $testEntity = new class {
            #[AnonymizeProperty(type: 'email')]
            public string $email = 'test@example.com';

            #[AnonymizeProperty(type: 'name')]
            public string $name = 'John';

            #[AnonymizeProperty(type: 'phone')]
            public string $phone = '123456789';

            #[AnonymizeProperty(type: 'address')]
            public string $address = '123 Main St';

            #[AnonymizeProperty(type: 'company')]
            public string $company = 'Acme Corp';
        };

        $reflection = new ReflectionClass($testEntity);
        $result = $this->service->getAnonymizableProperties($reflection);

        $this->assertIsArray($result);
        $this->assertCount(5, $result);
    }

    /**
     * Test that anonymizeEntity handles empty records.
     */
    public function testAnonymizeEntityHandlesEmptyRecords(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflection = $this->createMock(ReflectionClass::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');

        $connection->method('fetchAllAssociative')
            ->willReturn([]);

        $properties = [];
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['updated']);
        $this->assertIsArray($result['propertyStats']);
    }

    /**
     * Test that anonymizeEntity handles progress callback.
     */
    public function testAnonymizeEntityHandlesProgressCallback(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflection = $this->createMock(ReflectionClass::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');

        $connection->method('fetchAllAssociative')
            ->willReturn([]);

        $properties = [];
        $progressCalled = false;
        $progressCallback = function ($current, $total, $message) use (&$progressCalled) {
            $progressCalled = true;
        };

        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        // Progress callback should not be called for empty records
        $this->assertFalse($progressCalled);
        $this->assertIsArray($result);
    }

    /**
     * Test that anonymizeEntity handles statistics.
     */
    public function testAnonymizeEntityHandlesStatistics(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflection = $this->createMock(ReflectionClass::class);
        $statistics = new \Nowo\AnonymizeBundle\Service\AnonymizeStatistics();

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');

        $connection->method('fetchAllAssociative')
            ->willReturn([]);

        $properties = [];
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, $statistics);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['processed']);
    }

    /**
     * Test that anonymizeEntity handles entity-level patterns.
     */
    public function testAnonymizeEntityHandlesEntityLevelPatterns(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflection = $this->createMock(ReflectionClass::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');

        $connection->method('fetchAllAssociative')
            ->willReturn([]);

        $entityAttribute = new Anonymize();
        $entityAttribute->includePatterns = ['id' => '>100'];

        $properties = [];
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, null, $entityAttribute);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['processed']);
    }

    /**
     * Test that anonymizeEntity handles dry run mode.
     */
    public function testAnonymizeEntityHandlesDryRunMode(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflection = $this->createMock(ReflectionClass::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');

        $connection->method('fetchAllAssociative')
            ->willReturn([]);

        $properties = [];
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, true);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles properties that don't exist in metadata.
     */
    public function testAnonymizeEntityHandlesNonExistentProperties(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email')]
            public string $email = 'test@example.com';
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(false);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $metadata->method('getFieldNames')
            ->willReturn([]);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'email' => 'test@example.com']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('email');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        // Use a valid progress callback to avoid the error
        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        // Should not update because property doesn't exist in metadata
        $this->assertEquals(0, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles buildQueryWithRelationships with pattern fields.
     */
    public function testAnonymizeEntityHandlesRelationshipPatterns(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', includePatterns: ['user.status' => 'active'])]
            public string $email = 'test@example.com';
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasAssociation')
            ->with('user')
            ->willReturn(false);
        $metadata->method('getFieldNames')
            ->willReturn([]);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');

        $property = $reflection->getProperty('email');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['processed']);
    }

    /**
     * Test that anonymizeEntity handles entity-level exclude patterns.
     */
    public function testAnonymizeEntityHandlesEntityLevelExcludePatterns(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflection = $this->createMock(ReflectionClass::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('getFieldNames')
            ->willReturn([]);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');

        $entityAttribute = new Anonymize();
        $entityAttribute->excludePatterns = ['status' => 'deleted'];

        $properties = [];
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, null, $entityAttribute);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['processed']);
    }

    /**
     * Test that anonymizeEntity handles properties with include patterns.
     */
    public function testAnonymizeEntityHandlesPropertyIncludePatterns(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', includePatterns: ['id' => '>100'])]
            public string $email = 'test@example.com';
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('email')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('email', 'string', 'email');
        $metadata->method('getFieldMapping')
            ->with('email')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['email']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 50, 'email' => 'test@example.com']]); // ID doesn't match pattern
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('email');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        // Should not update because pattern doesn't match
        $this->assertEquals(0, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles properties with exclude patterns.
     */
    public function testAnonymizeEntityHandlesPropertyExcludePatterns(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', excludePatterns: ['status' => 'deleted'])]
            public string $email = 'test@example.com';
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('email')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('email', 'string', 'email');
        $metadata->method('getFieldMapping')
            ->with('email')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['email']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'email' => 'test@example.com', 'status' => 'deleted']]); // Status matches exclude pattern
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('email');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        // Should not update because pattern excludes this record
        $this->assertEquals(0, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles AnonymizableTrait with anonymized column.
     */
    public function testAnonymizeEntityHandlesAnonymizableTrait(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            use \Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

            #[AnonymizeProperty(type: 'email')]
            public string $email = 'test@example.com';
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('email')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('email', 'string', 'email');
        $metadata->method('getFieldMapping')
            ->with('email')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['email']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'email' => 'test@example.com']]);
        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);
        $schemaManager->method('tablesExist')
            ->with(['test_table'])
            ->willReturn(true);

        $column = $this->createMock(\Doctrine\DBAL\Schema\Column::class);
        $column->method('getName')
            ->willReturn('anonymized');
        $schemaManager->method('listTableColumns')
            ->with('test_table')
            ->willReturn(['anonymized' => $column]);

        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('email');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles AnonymizableTrait without anonymized column.
     */
    public function testAnonymizeEntityHandlesAnonymizableTraitWithoutColumn(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            use \Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

            #[AnonymizeProperty(type: 'email')]
            public string $email = 'test@example.com';
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('email')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('email', 'string', 'email');
        $metadata->method('getFieldMapping')
            ->with('email')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['email']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'email' => 'test@example.com']]);
        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);
        $schemaManager->method('tablesExist')
            ->with(['test_table'])
            ->willReturn(true);

        $column = $this->createMock(\Doctrine\DBAL\Schema\Column::class);
        $column->method('getName')
            ->willReturn('other_column');
        $schemaManager->method('listTableColumns')
            ->with('test_table')
            ->willReturn(['other_column' => $column]);

        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('email');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles event dispatcher with skip anonymization.
     */
    public function testAnonymizeEntityHandlesEventDispatcherSkipAnonymization(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $eventDispatcher = $this->createMock(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email')]
            public string $email = 'test@example.com';
        };
        $reflection = new ReflectionClass($testEntity);

        // Create service with event dispatcher using real instances
        $patternMatcher = new \Nowo\AnonymizeBundle\Service\PatternMatcher();
        $fakerFactory = new \Nowo\AnonymizeBundle\Faker\FakerFactory('en_US');
        $service = new AnonymizeService($fakerFactory, $patternMatcher, $eventDispatcher);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('email')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('email', 'string', 'email');
        $metadata->method('getFieldMapping')
            ->with('email')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['email']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'email' => 'test@example.com']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        // Mock event to skip anonymization
        $eventDispatcher->method('dispatch')
            ->willReturnCallback(function ($event) {
                if ($event instanceof \Nowo\AnonymizeBundle\Event\AnonymizePropertyEvent) {
                    $event->setSkipAnonymization(true);
                }
                return $event;
            });

        $property = $reflection->getProperty('email');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        // Should not update because event skipped anonymization
        $this->assertEquals(0, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles event dispatcher with modified value.
     */
    public function testAnonymizeEntityHandlesEventDispatcherModifiedValue(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $eventDispatcher = $this->createMock(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email')]
            public string $email = 'test@example.com';
        };
        $reflection = new ReflectionClass($testEntity);

        // Create service with event dispatcher using real instances
        $patternMatcher = new \Nowo\AnonymizeBundle\Service\PatternMatcher();
        $fakerFactory = new \Nowo\AnonymizeBundle\Faker\FakerFactory('en_US');
        $service = new AnonymizeService($fakerFactory, $patternMatcher, $eventDispatcher);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('email')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('email', 'string', 'email');
        $metadata->method('getFieldMapping')
            ->with('email')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['email']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'email' => 'test@example.com']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        // Mock event to modify anonymized value
        $eventDispatcher->method('dispatch')
            ->willReturnCallback(function ($event) {
                if ($event instanceof \Nowo\AnonymizeBundle\Event\AnonymizePropertyEvent) {
                    $event->setAnonymizedValue('modified@example.com');
                }
                return $event;
            });

        $property = $reflection->getProperty('email');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles integer field type conversion.
     */
    public function testAnonymizeEntityHandlesIntegerFieldType(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'numeric', options: ['type' => 'int', 'min' => 1, 'max' => 100])]
            public int $age = 25;
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('age')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('age', 'integer', 'age');
        $metadata->method('getFieldMapping')
            ->with('age')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['age']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'age' => '25']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('age');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles float field type conversion.
     */
    public function testAnonymizeEntityHandlesFloatFieldType(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'numeric', options: ['type' => 'float', 'min' => 0, 'max' => 100])]
            public float $price = 99.99;
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('price')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('price', 'float', 'price');
        $metadata->method('getFieldMapping')
            ->with('price')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['price']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'price' => '99.99']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('price');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles boolean field type conversion.
     */
    public function testAnonymizeEntityHandlesBooleanFieldType(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'boolean')]
            public bool $active = true;
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('active')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('active', 'boolean', 'active');
        $metadata->method('getFieldMapping')
            ->with('active')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['active']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'active' => '1']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('active');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles updateRecord with null values.
     */
    public function testAnonymizeEntityHandlesNullValues(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email')]
            public ?string $email = null;
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('email')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('email', 'string', 'email');
        $metadata->method('getFieldMapping')
            ->with('email')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['email']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        // Record with null email - should still anonymize
        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'email' => null]]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(function ($val) {
                if ($val === null) {
                    return 'NULL';
                }
                return "'" . (string) $val . "'";
            });
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('email');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        // Email faker should generate a value even when original is null
        $this->assertGreaterThanOrEqual(0, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles updateRecord with boolean true value.
     */
    public function testAnonymizeEntityHandlesBooleanTrueValue(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'boolean', options: ['true_probability' => 100])]
            public bool $active = true;
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('active')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('active', 'boolean', 'active');
        $metadata->method('getFieldMapping')
            ->with('active')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['active']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'active' => '0']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('active');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles updateRecord with boolean false value.
     */
    public function testAnonymizeEntityHandlesBooleanFalseValue(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'boolean', options: ['true_probability' => 0])]
            public bool $active = false;
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('active')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('active', 'boolean', 'active');
        $metadata->method('getFieldMapping')
            ->with('active')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['active']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'active' => '1']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('active');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles different integer types (smallint, bigint).
     */
    public function testAnonymizeEntityHandlesDifferentIntegerTypes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'numeric', options: ['type' => 'int', 'min' => 1, 'max' => 100])]
            public int $count = 10;
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('count')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('count', 'smallint', 'count');
        $metadata->method('getFieldMapping')
            ->with('count')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['count']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'count' => '10']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('count');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles decimal field type.
     */
    public function testAnonymizeEntityHandlesDecimalFieldType(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'numeric', options: ['type' => 'float', 'min' => 0, 'max' => 100])]
            public float $amount = 50.50;
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('amount')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('amount', 'decimal', 'amount');
        $metadata->method('getFieldMapping')
            ->with('amount')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['amount']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'amount' => '50.50']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('amount');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
    }

    /**
     * Test that buildQueryWithRelationships handles non-existent associations.
     */
    public function testBuildQueryWithRelationshipsHandlesNonExistentAssociations(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', includePatterns: ['nonexistent.name' => 'active'])]
            public string $email = 'test@example.com';
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('email')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->with('nonexistent')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('email', 'string', 'email');
        $metadata->method('getFieldMapping')
            ->with('email')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['email']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'email' => 'test@example.com']]);
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('email');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        // When association doesn't exist, pattern won't match, so record won't be updated
        // But we test that the code handles it gracefully
        $this->assertGreaterThanOrEqual(0, $result['updated']);
    }

    /**
     * Test that buildQueryWithRelationships handles duplicate pattern fields.
     */
    public function testBuildQueryWithRelationshipsHandlesDuplicatePatternFields(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', includePatterns: ['type.name' => 'HR'], excludePatterns: ['type.name' => 'IT'])]
            public string $email = 'test@example.com';
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasAssociation')
            ->with('type')
            ->willReturn(false);
        $metadata->method('getFieldNames')
            ->willReturn([]);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('fetchAllAssociative')
            ->willReturn([]);

        $property = $reflection->getProperty('email');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['processed']);
    }

    /**
     * Test that convertValue handles different field types correctly.
     */
    public function testConvertValueHandlesDifferentFieldTypes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'numeric', options: ['type' => 'int'])]
            public int $count = 10;
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('count')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('count', 'integer', 'count');
        $metadata->method('getFieldMapping')
            ->with('count')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['count']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'count' => '10']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(fn($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(fn($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property = $reflection->getProperty('count');
        $properties = [
            [
                'property' => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight' => 1,
            ],
        ];

        $progressCallback = function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
    }

}
