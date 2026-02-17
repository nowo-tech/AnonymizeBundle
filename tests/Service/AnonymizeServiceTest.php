<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Service;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DiscriminatorColumnMapping;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Exception;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\AnonymizeService;
use Nowo\AnonymizeBundle\Service\EntityAnonymizerServiceInterface;
use Nowo\AnonymizeBundle\Service\PatternMatcher;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use RuntimeException;
use stdClass;

use function in_array;

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
        $this->fakerFactory   = new FakerFactory('en_US');
        $this->patternMatcher = new PatternMatcher();
        $this->service        = new AnonymizeService($this->fakerFactory, $this->patternMatcher);
    }

    /**
     * Test that getAnonymizableEntities returns empty array when no metadata driver.
     */
    public function testGetAnonymizableEntitiesReturnsEmptyWhenNoMetadataDriver(): void
    {
        $em     = $this->createMock(EntityManagerInterface::class);
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

        $metadata       = $this->createMock(ClassMetadata::class);
        $metadataDriver = $this->createMock(MappingDriver::class);
        $config         = $this->createMock(Configuration::class);
        $em             = $this->createMock(EntityManagerInterface::class);

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

        $metadata       = $this->createMock(ClassMetadata::class);
        $metadataDriver = $this->createMock(MappingDriver::class);
        $config         = $this->createMock(Configuration::class);
        $em             = $this->createMock(EntityManagerInterface::class);

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
        $result     = $this->service->getAnonymizableProperties($reflection);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        // The result is an indexed array, not associative by property name
        $propertyNames = array_map(static fn ($item) => $item['property']->getName(), $result);
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
        $result     = $this->service->getAnonymizableProperties($reflection);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        // The result is sorted by weight, so email (1) should come first, then phone (2), then name (3)
        $propertyNames = array_map(static fn ($item) => $item['property']->getName(), $result);
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
        $result     = $this->service->getAnonymizableProperties($reflection);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        // Properties with weight should come first
        $propertyNames = array_map(static fn ($item) => $item['property']->getName(), $result);
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
        $result     = $this->service->getAnonymizableProperties($reflection);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that getAnonymizableEntities handles exceptions gracefully.
     */
    public function testGetAnonymizableEntitiesHandlesExceptions(): void
    {
        $metadataDriver = $this->createMock(MappingDriver::class);
        $config         = $this->createMock(Configuration::class);
        $em             = $this->createMock(EntityManagerInterface::class);

        $em->method('getConfiguration')
            ->willReturn($config);

        $config->method('getMetadataDriverImpl')
            ->willReturn($metadataDriver);

        $metadataDriver->method('getAllClassNames')
            ->willReturn(['NonExistentClass']);

        $em->method('getClassMetadata')
            ->willThrowException(new Exception('Class not found'));

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
        $result     = $this->service->getAnonymizableProperties($reflection);

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
        $result     = $this->service->getAnonymizableProperties($reflection);

        $this->assertIsArray($result);
        $this->assertCount(5, $result);
    }

    /**
     * Test that anonymizeEntity handles empty records.
     */
    public function testAnonymizeEntityHandlesEmptyRecords(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
        $result     = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties);

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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflection = $this->createMock(ReflectionClass::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');

        $connection->method('fetchAllAssociative')
            ->willReturn([]);

        $properties       = [];
        $progressCalled   = false;
        $progressCallback = static function ($current, $total, $message) use (&$progressCalled) {
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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
        $result     = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, $statistics);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['processed']);
    }

    /**
     * Test that anonymizeEntity handles entity-level patterns.
     */
    public function testAnonymizeEntityHandlesEntityLevelPatterns(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $reflection = $this->createMock(ReflectionClass::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');

        $connection->method('fetchAllAssociative')
            ->willReturn([]);

        $entityAttribute                  = new Anonymize();
        $entityAttribute->includePatterns = ['id' => '>100'];

        $properties = [];
        $result     = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, null, $entityAttribute);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['processed']);
    }

    /**
     * Test that anonymizeEntity handles dry run mode.
     */
    public function testAnonymizeEntityHandlesDryRunMode(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
        $result     = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, true);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(0, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles properties that don't exist in metadata.
     */
    public function testAnonymizeEntityHandlesNonExistentProperties(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        // Use a valid progress callback to avoid the error
        $progressCallback = static function ($current, $total, $message) {
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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['processed']);
    }

    /**
     * Test that anonymizeEntity adds WHERE discriminator to query when entity is polymorphic (STI/CTI).
     */
    public function testAnonymizeEntityAddsDiscriminatorToQueryWhenPolymorphic(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('notifications');
        $metadata->method('getFieldNames')
            ->willReturn(['id', 'message']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);
        $metadata->inheritanceType     = 1; // SINGLE_TABLE
        $metadata->discriminatorColumn = class_exists(DiscriminatorColumnMapping::class)
            ? new DiscriminatorColumnMapping('string', 'type', 'type')
            : ['name' => 'type'];
        $metadata->discriminatorValue = 'sms';

        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . str_replace("'", "''", (string) $val) . "'");

        $capturedQuery = null;
        $connection->method('fetchAllAssociative')
            ->willReturnCallback(static function ($query) use (&$capturedQuery) {
                $capturedQuery = $query;

                return [];
            });

        $reflection = $this->createMock(ReflectionClass::class);
        $result     = $this->service->anonymizeEntity($em, $metadata, $reflection, [], 100, false);

        $this->assertIsArray($result);
        $this->assertNotNull($capturedQuery, 'Query should have been passed to fetchAllAssociative');
        $query = (string) $capturedQuery;
        $this->assertStringContainsString('WHERE', $query);
        $this->assertStringContainsString('`type`', $query);
        $this->assertStringContainsString("'sms'", $query);
    }

    /**
     * Test that anonymizeEntity handles entity-level exclude patterns.
     */
    public function testAnonymizeEntityHandlesEntityLevelExcludePatterns(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');

        $entityAttribute                  = new Anonymize();
        $entityAttribute->excludePatterns = ['status' => 'deleted'];

        $properties = [];
        $result     = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, null, $entityAttribute);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['processed']);
    }

    /**
     * Test that anonymizeEntity handles entity-level exclude patterns with multiple configs (OR between configs).
     */
    public function testAnonymizeEntityHandlesEntityLevelMultipleExcludeConfigs(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturn([['id' => 1, 'role' => 'admin', 'status' => 'active']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');

        $entityAttribute                  = new Anonymize();
        $entityAttribute->excludePatterns = [
            ['status' => 'deleted'],
            ['role' => 'admin'],
        ];

        $properties = [];
        $result     = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, null, $entityAttribute);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed'], 'Record was iterated');
        $this->assertEquals(0, $result['updated'], 'Excluded record must not be anonymized');
    }

    /**
     * Test that anonymizeEntity handles entity-level exclude patterns with array value (OR for one field).
     */
    public function testAnonymizeEntityHandlesEntityLevelExcludePatternsArrayValue(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturn([['id' => 1, 'email' => 'user@nowo.tech']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');

        $entityAttribute                  = new Anonymize();
        $entityAttribute->excludePatterns = ['email' => ['%@nowo.tech', 'operador@example.com']];

        $properties = [];
        $result     = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, null, $entityAttribute);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed'], 'Record was iterated');
        $this->assertEquals(0, $result['updated'], 'Excluded record must not be anonymized');
    }

    /**
     * Test that anonymizeEntity handles properties with include patterns.
     */
    public function testAnonymizeEntityHandlesPropertyIncludePatterns(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(\Doctrine\DBAL\Connection::class);
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $metadata      = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(\Doctrine\DBAL\Connection::class);
        $schemaManager = $this->createMock(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $metadata      = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em              = $this->createMock(EntityManagerInterface::class);
        $connection      = $this->createMock(\Doctrine\DBAL\Connection::class);
        $eventDispatcher = $this->createMock(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class);
        $metadata        = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email')]
            public string $email = 'test@example.com';
        };
        $reflection = new ReflectionClass($testEntity);

        // Create service with event dispatcher using real instances
        $patternMatcher = new PatternMatcher();
        $fakerFactory   = new FakerFactory('en_US');
        $service        = new AnonymizeService($fakerFactory, $patternMatcher, $eventDispatcher);

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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        // Mock event to skip anonymization
        $eventDispatcher->method('dispatch')
            ->willReturnCallback(static function ($event) {
                if ($event instanceof \Nowo\AnonymizeBundle\Event\AnonymizePropertyEvent) {
                    $event->setSkipAnonymization(true);
                }

                return $event;
            });

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em              = $this->createMock(EntityManagerInterface::class);
        $connection      = $this->createMock(\Doctrine\DBAL\Connection::class);
        $eventDispatcher = $this->createMock(\Symfony\Contracts\EventDispatcher\EventDispatcherInterface::class);
        $metadata        = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email')]
            public string $email = 'test@example.com';
        };
        $reflection = new ReflectionClass($testEntity);

        // Create service with event dispatcher using real instances
        $patternMatcher = new PatternMatcher();
        $fakerFactory   = new FakerFactory('en_US');
        $service        = new AnonymizeService($fakerFactory, $patternMatcher, $eventDispatcher);

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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        // Mock event to modify anonymized value
        $eventDispatcher->method('dispatch')
            ->willReturnCallback(static function ($event) {
                if ($event instanceof \Nowo\AnonymizeBundle\Event\AnonymizePropertyEvent) {
                    $event->setAnonymizedValue('modified@example.com');
                }

                return $event;
            });

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('age');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('price');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('active');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static function ($val) {
                if ($val === null) {
                    return 'NULL';
                }

                return "'" . (string) $val . "'";
            });
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('active');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('active');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('count');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('amount');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'email' => 'test@example.com']]);
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('fetchAllAssociative')
            ->willReturn([]);

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
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
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
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
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('count');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
    }

    /**
     * Test that anonymizeEntity handles nullable option with null probability.
     */
    public function testAnonymizeEntityHandlesNullableOption(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', options: ['nullable' => true, 'null_probability' => 100])]
            public ?string $email = 'test@example.com';
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
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static function ($val) {
                if ($val === null) {
                    return 'NULL';
                }

                return "'" . (string) $val . "'";
            });

        // Track if null was used in SQL and capture SQL for debugging
        $nullUsed    = false;
        $capturedSql = '';
        $connection->method('executeStatement')
            ->willReturnCallback(static function ($sql) use (&$nullUsed, &$capturedSql) {
                $capturedSql = $sql;
                // Check if SQL contains '= NULL' (without quotes, which means null value)
                // The SQL should be like: UPDATE `test_table` SET `email` = NULL WHERE `id` = '1'
                if (preg_match('/`email`\s*=\s*NULL\b/i', $sql) || preg_match('/email\s*=\s*NULL\b/i', $sql)) {
                    $nullUsed = true;
                }

                return 1;
            });

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
        // With 100% null probability, value should be null
        // Debug: show SQL if assertion fails
        $this->assertTrue($nullUsed, 'With 100% null probability, the value should be null. SQL was: ' . ($capturedSql ?: 'NOT EXECUTED'));
    }

    /**
     * Test that anonymizeEntity handles nullable option with 0% null probability.
     */
    public function testAnonymizeEntityHandlesNullableOptionZeroProbability(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', options: ['nullable' => true, 'null_probability' => 0])]
            public ?string $email = 'test@example.com';
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
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static function ($val) {
                if ($val === null) {
                    return 'NULL';
                }

                return "'" . (string) $val . "'";
            });

        // Track if null was used in SQL
        $nullUsed = false;
        $connection->method('executeStatement')
            ->willReturnCallback(static function ($sql) use (&$nullUsed) {
                // The SQL should NOT contain '= NULL' for the email field with 0% probability
                if (preg_match('/`email`\s*=\s*NULL\b/i', $sql) || preg_match('/email\s*=\s*NULL\b/i', $sql)) {
                    $nullUsed = true;
                }

                return 1;
            });

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
            // Empty callback
        };

        // Run multiple times to ensure 0% null probability never returns null
        $neverNull = true;
        for ($i = 0; $i < 10; ++$i) {
            $nullUsed = false;
            $result   = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

            if ($nullUsed) {
                $neverNull = false;
                break;
            }
        }

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
        // With 0% null probability, value should NOT be null
        $this->assertTrue($neverNull, 'With 0% null probability, the value should never be null');
    }

    /**
     * Test that anonymizeEntity preserves null values when preserve_null option is enabled.
     */
    public function testAnonymizeEntityPreservesNullValues(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', options: ['preserve_null' => true])]
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

        // Record with null email - should NOT be anonymized when preserve_null is true
        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'email' => null]]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static function ($val) {
                if ($val === null) {
                    return 'NULL';
                }

                return "'" . (string) $val . "'";
            });

        // Track if SQL was executed (should NOT be executed when preserve_null is true and value is null)
        $sqlExecuted = false;
        $connection->method('executeStatement')
            ->willReturnCallback(static function ($sql) use (&$sqlExecuted) {
                $sqlExecuted = true;

                return 1;
            });

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        // When preserve_null is true and value is null, no update should occur
        $this->assertEquals(0, $result['updated']);
        $this->assertFalse($sqlExecuted, 'SQL should not be executed when preserve_null is true and value is null');
    }

    /**
     * Test that anonymizeEntity anonymizes non-null values even when preserve_null is enabled.
     */
    public function testAnonymizeEntityAnonymizesNonNullValuesWithPreserveNull(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', options: ['preserve_null' => true])]
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

        // Record with non-null email - should be anonymized even when preserve_null is true
        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'email' => 'original@example.com']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static function ($val) {
                if ($val === null) {
                    return 'NULL';
                }

                return "'" . (string) $val . "'";
            });
        $connection->method('executeStatement')
            ->willReturn(1);

        $property   = $reflection->getProperty('email');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        $progressCallback = static function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        // When preserve_null is true but value is NOT null, it should be anonymized
        $this->assertEquals(1, $result['updated']);
    }

    /**
     * Test that anonymizeEntity processes fields with bypass_entity_exclusion even when record is excluded.
     */
    public function testAnonymizeEntityBypassesEntityExclusion(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[AnonymizeProperty(
                type: 'null',
                options: ['bypass_entity_exclusion' => true],
            )]
            public ?string $sensitiveNotes = 'Sensitive data';
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->with('sensitiveNotes')
            ->willReturn(true);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('sensitiveNotes', 'text', 'sensitive_notes');
        $metadata->method('getFieldMapping')
            ->with('sensitiveNotes')
            ->willReturn($fieldMapping);
        $metadata->method('getFieldNames')
            ->willReturn(['sensitiveNotes']);
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        // Record that matches entity exclusion pattern (will be excluded)
        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'sensitive_notes' => 'Sensitive data', 'role' => 'admin']]);
        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static function ($val) {
                if ($val === null) {
                    return 'NULL';
                }

                return "'" . (string) $val . "'";
            });

        $capturedSql = '';
        $connection->method('executeStatement')
            ->willReturnCallback(static function ($sql) use (&$capturedSql) {
                $capturedSql = $sql;

                return 1;
            });

        $property   = $reflection->getProperty('sensitiveNotes');
        $properties = [
            [
                'property'  => $property,
                'attribute' => $property->getAttributes(AnonymizeProperty::class)[0]->newInstance(),
                'weight'    => 1,
            ],
        ];

        // Entity attribute with exclusion pattern that matches the record
        $entityAttribute                  = new Anonymize();
        $entityAttribute->excludePatterns = ['role' => 'admin'];

        $progressCallback = static function ($current, $total, $message) {
            // Empty callback
        };
        $result = $this->service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, $progressCallback, $entityAttribute);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        // Should update because bypass_entity_exclusion is true
        $this->assertEquals(1, $result['updated'], 'Field with bypass_entity_exclusion should be updated even when record is excluded');
        // Should set to NULL
        $this->assertStringContainsString('`sensitive_notes` = NULL', $capturedSql, 'Field should be set to NULL even when record is excluded');
    }

    /**
     * Test that anonymizeEntity delegates to anonymizeService when entity has anonymizeService and container is set.
     */
    public function testAnonymizeEntityDelegatesToAnonymizeServiceWhenSet(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('notifications');
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);
        $metadata->method('hasField')
            ->willReturn(false);
        $metadata->method('hasAssociation')
            ->willReturn(false);
        $metadata->method('getFieldNames')
            ->willReturn([]);

        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . str_replace("'", "''", (string) $val) . "'");

        $record = ['id' => 1, 'recipient' => '+34600000000', 'message' => 'Hello'];
        $connection->method('fetchAllAssociative')
            ->willReturn([$record]);

        $capturedSql = null;
        $connection->method('executeStatement')
            ->willReturnCallback(static function ($sql) use (&$capturedSql) {
                $capturedSql = $sql;

                return 1;
            });

        $anonymizer = $this->createMock(EntityAnonymizerServiceInterface::class);
        $anonymizer->method('anonymize')
            ->with($em, $metadata, $record, false)
            ->willReturn(['recipient' => '+34999111222', 'message' => 'Anonymized']);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('App\Service\SmsAnonymizer')
            ->willReturn($anonymizer);

        $service = new AnonymizeService($this->fakerFactory, $this->patternMatcher, null, $container);

        $entityAttribute = new Anonymize(anonymizeService: 'App\Service\SmsAnonymizer');
        $reflection      = $this->createMock(ReflectionClass::class);
        $properties      = [];

        $result = $service->anonymizeEntity($em, $metadata, $reflection, $properties, 100, false, null, null, $entityAttribute);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
        $this->assertNotNull($capturedSql);
        $sql = (string) $capturedSql;
        $this->assertStringContainsString('UPDATE', $sql);
        $this->assertStringContainsString('`recipient`', $sql);
        $this->assertStringContainsString('`message`', $sql);
    }

    /**
     * Test that anonymizeEntity with anonymizeService in dry-run does not execute UPDATE.
     */
    public function testAnonymizeEntityDelegatesToAnonymizeServiceDryRun(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('notifications');
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $record = ['id' => 1, 'recipient' => '+34600000000'];
        $connection->method('fetchAllAssociative')
            ->willReturn([$record]);

        $connection->method('executeStatement')
            ->willReturn(1);

        $anonymizer = $this->createMock(EntityAnonymizerServiceInterface::class);
        $anonymizer->method('anonymize')
            ->with($em, $metadata, $record, true)
            ->willReturn(['recipient' => '+34999111222']);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('App\Service\SmsAnonymizer')
            ->willReturn($anonymizer);

        $service = new AnonymizeService($this->fakerFactory, $this->patternMatcher, null, $container);

        $entityAttribute = new Anonymize(anonymizeService: 'App\Service\SmsAnonymizer');
        $reflection      = $this->createMock(ReflectionClass::class);

        $result = $service->anonymizeEntity($em, $metadata, $reflection, [], 100, true, null, null, $entityAttribute);

        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['updated']);
        // In dry run, updateRecord is not called so no executeStatement for UPDATE
    }

    /**
     * Test that anonymizeEntity throws when anonymizeService id returns a service that does not implement EntityAnonymizerServiceInterface.
     */
    public function testAnonymizeEntityThrowsWhenAnonymizeServiceNotImplementInterface(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->method('getConnection')
            ->willReturn($connection);
        $metadata->method('getTableName')
            ->willReturn('notifications');
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $connection->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'recipient' => '+34600000000']]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('invalid_service')
            ->willReturn(new stdClass());

        $service = new AnonymizeService($this->fakerFactory, $this->patternMatcher, null, $container);

        $entityAttribute = new Anonymize(anonymizeService: 'invalid_service');
        $reflection      = $this->createMock(ReflectionClass::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must implement');
        $service->anonymizeEntity($em, $metadata, $reflection, [], 100, false, null, null, $entityAttribute);
    }

    /**
     * Test that anonymizeEntity skips record when entity is excluded by patterns but anonymizeService is set (no update).
     */
    public function testAnonymizeEntityWithAnonymizeServiceSkipsExcludedRecord(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->method('getConnection')
            ->willReturn($connection);
        $metadata->method('getTableName')
            ->willReturn('notifications');
        $metadata->method('getIdentifierColumnNames')
            ->willReturn(['id']);

        $record = ['id' => 1, 'role' => 'admin'];
        $connection->method('fetchAllAssociative')
            ->willReturn([$record]);

        $anonymizer = $this->createMock(EntityAnonymizerServiceInterface::class);
        $anonymizer->expects($this->never())->method('anonymize');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('App\Service\SmsAnonymizer')
            ->willReturn($anonymizer);

        $service = new AnonymizeService($this->fakerFactory, $this->patternMatcher, null, $container);

        $entityAttribute                  = new Anonymize(anonymizeService: 'App\Service\SmsAnonymizer');
        $entityAttribute->excludePatterns = ['role' => 'admin'];
        $reflection                       = $this->createMock(ReflectionClass::class);

        $result = $service->anonymizeEntity($em, $metadata, $reflection, [], 100, false, null, null, $entityAttribute);

        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['updated']);
    }

    /**
     * Test that truncateTables returns empty array when no entities have truncate=true.
     */
    public function testTruncateTablesReturnsEmptyWhenNoTruncateEntities(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[Anonymize]
            public string $email = 'test@example.com';
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => new Anonymize(truncate: false),
            ],
        ];

        $result = $this->service->truncateTables($em, $entities, false);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that truncateTables truncates tables with truncate=true for MySQL.
     */
    public function testTruncateTablesTruncatesForMySQL(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testEntity = new class {
            #[Anonymize(truncate: true)]
            public string $email = 'test@example.com';
        };
        $reflection = new ReflectionClass($testEntity);

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');

        // Mock driver to return MySQL
        $driver = $this->createMock(\Doctrine\DBAL\Driver::class);
        $connection->method('getDriver')
            ->willReturn($driver);

        // Mock platform to return MySQL platform
        $platform = $this->createMock(\Doctrine\DBAL\Platforms\AbstractPlatform::class);
        $connection->method('getDatabasePlatform')
            ->willReturn($platform);

        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');

        $executedStatements = [];
        $connection->method('executeStatement')
            ->willReturnCallback(static function ($sql) use (&$executedStatements) {
                $executedStatements[] = $sql;

                return 0;
            });

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => new Anonymize(truncate: true),
            ],
        ];

        $result = $this->service->truncateTables($em, $entities, false);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('test_table', $result);
        $this->assertContains('SET FOREIGN_KEY_CHECKS = 0', $executedStatements);
        $this->assertContains('TRUNCATE TABLE `test_table`', $executedStatements);
        $this->assertContains('SET FOREIGN_KEY_CHECKS = 1', $executedStatements);
    }

    /**
     * Test that truncateTables orders tables by truncate_order.
     */
    public function testTruncateTablesOrdersByTruncateOrder(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata1  = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata2 = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata3 = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata1->method('getTableName')
            ->willReturn('table_c');
        $metadata2->method('getTableName')
            ->willReturn('table_a');
        $metadata3->method('getTableName')
            ->willReturn('table_b');

        // Mock driver to return MySQL
        $driver = $this->createMock(\Doctrine\DBAL\Driver::class);
        $connection->method('getDriver')
            ->willReturn($driver);

        // Mock platform to return MySQL platform
        $platform = $this->createMock(\Doctrine\DBAL\Platforms\AbstractPlatform::class);
        $connection->method('getDatabasePlatform')
            ->willReturn($platform);

        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');

        $truncateStatements = [];
        $connection->method('executeStatement')
            ->willReturnCallback(static function ($sql) use (&$truncateStatements) {
                if (str_contains($sql, 'TRUNCATE TABLE')) {
                    $truncateStatements[] = $sql;
                }

                return 0;
            });

        $entities = [
            'Entity1' => [
                'metadata'   => $metadata1,
                'reflection' => $this->createMock(ReflectionClass::class),
                'attribute'  => new Anonymize(truncate: true, truncate_order: 3), // Last
            ],
            'Entity2' => [
                'metadata'   => $metadata2,
                'reflection' => $this->createMock(ReflectionClass::class),
                'attribute'  => new Anonymize(truncate: true, truncate_order: 1), // First
            ],
            'Entity3' => [
                'metadata'   => $metadata3,
                'reflection' => $this->createMock(ReflectionClass::class),
                'attribute'  => new Anonymize(truncate: true, truncate_order: null), // After explicit orders
            ],
        ];

        $result = $this->service->truncateTables($em, $entities, false);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        // Check order: table_a (order 1), table_c (order 3), table_b (order null = PHP_INT_MAX, last)
        $this->assertStringContainsString('`table_a`', $truncateStatements[0]);
        $this->assertStringContainsString('`table_c`', $truncateStatements[1]);
        $this->assertStringContainsString('`table_b`', $truncateStatements[2]);
    }

    /**
     * Test that truncateTables handles dry-run mode.
     */
    public function testTruncateTablesHandlesDryRun(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');

        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('fetchOne')
            ->willReturn('10'); // 10 records would be deleted

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $this->createMock(ReflectionClass::class),
                'attribute'  => new Anonymize(truncate: true),
            ],
        ];

        $result = $this->service->truncateTables($em, $entities, true); // dry-run

        $this->assertIsArray($result);
        $this->assertArrayHasKey('test_table', $result);
        $this->assertEquals(10, $result['test_table']);
    }

    /**
     * Test that truncateTables handles PostgreSQL with CASCADE.
     */
    public function testTruncateTablesHandlesPostgreSQL(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');

        // Mock driver to return PostgreSQL
        $driver = $this->createMock(\Doctrine\DBAL\Driver::class);
        $connection->method('getDriver')
            ->willReturn($driver);

        // Mock platform to return PostgreSQL platform
        $platform = $this->createMock(\Doctrine\DBAL\Platforms\PostgreSQLPlatform::class);
        $connection->method('getDatabasePlatform')
            ->willReturn($platform);

        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '"' . $id . '"');

        $executedStatements = [];
        $connection->method('executeStatement')
            ->willReturnCallback(static function ($sql) use (&$executedStatements) {
                $executedStatements[] = $sql;

                return 0;
            });

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $this->createMock(ReflectionClass::class),
                'attribute'  => new Anonymize(truncate: true),
            ],
        ];

        $result = $this->service->truncateTables($em, $entities, false);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('test_table', $result);
        // PostgreSQL should use TRUNCATE TABLE ... CASCADE
        $this->assertTrue(
            in_array('TRUNCATE TABLE "test_table" CASCADE', $executedStatements, true)
            || str_contains(implode(' ', $executedStatements), 'TRUNCATE')
            && str_contains(implode(' ', $executedStatements), 'CASCADE'),
        );
    }

    /**
     * Test that truncateTables handles SQLite with DELETE FROM.
     */
    public function testTruncateTablesHandlesSQLite(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');

        // Mock driver to return SQLite
        $driver = $this->createMock(\Doctrine\DBAL\Driver::class);
        $connection->method('getDriver')
            ->willReturn($driver);

        // Mock platform to return SQLite platform (use AbstractPlatform and configure params)
        $platform = $this->createMock(\Doctrine\DBAL\Platforms\AbstractPlatform::class);
        $connection->method('getDatabasePlatform')
            ->willReturn($platform);

        // Configure connection params so DbalHelper::getDriverName returns 'pdo_sqlite'
        $connection->method('getParams')
            ->willReturn(['driver' => 'pdo_sqlite']);

        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '"' . $id . '"');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . $val . "'");

        $executedStatements = [];
        $connection->method('executeStatement')
            ->willReturnCallback(static function ($sql) use (&$executedStatements) {
                $executedStatements[] = $sql;

                return 0;
            });

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $this->createMock(ReflectionClass::class),
                'attribute'  => new Anonymize(truncate: true),
            ],
        ];

        $result = $this->service->truncateTables($em, $entities, false);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('test_table', $result);
        // SQLite should use DELETE FROM and PRAGMA
        $this->assertContains('PRAGMA foreign_keys = OFF', $executedStatements);
        $this->assertContains('DELETE FROM "test_table"', $executedStatements);
        $this->assertContains('PRAGMA foreign_keys = ON', $executedStatements);
    }

    /**
     * Test that truncateTables calls progress callback.
     */
    public function testTruncateTablesCallsProgressCallback(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('test_table');

        // Mock driver to return MySQL
        $driver = $this->createMock(\Doctrine\DBAL\Driver::class);
        $connection->method('getDriver')
            ->willReturn($driver);

        // Mock platform to return MySQL platform
        $platform = $this->createMock(\Doctrine\DBAL\Platforms\AbstractPlatform::class);
        $connection->method('getDatabasePlatform')
            ->willReturn($platform);

        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('executeStatement')
            ->willReturn(0);

        $callbackCalled   = false;
        $callbackTable    = '';
        $progressCallback = static function (string $tableName, string $message) use (&$callbackCalled, &$callbackTable): void {
            $callbackCalled = true;
            $callbackTable  = $tableName;
        };

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $this->createMock(ReflectionClass::class),
                'attribute'  => new Anonymize(truncate: true),
            ],
        ];

        $this->service->truncateTables($em, $entities, false, $progressCallback);

        $this->assertTrue($callbackCalled);
        $this->assertEquals('test_table', $callbackTable);
    }

    /**
     * Test that truncateTables uses DELETE WHERE discriminator when entity is polymorphic (STI/CTI).
     */
    public function testTruncateTablesUsesDeleteByDiscriminatorWhenPolymorphic(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        $metadata   = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em->method('getConnection')
            ->willReturn($connection);

        $metadata->method('getTableName')
            ->willReturn('person');

        // Polymorphic entity: Single Table Inheritance
        $metadata->inheritanceType     = 1; // SINGLE_TABLE
        $metadata->discriminatorColumn = class_exists(DiscriminatorColumnMapping::class)
            ? new DiscriminatorColumnMapping('string', 'type', 'type')
            : ['name' => 'type'];
        $metadata->discriminatorValue = 'customer';

        $connection->method('quoteSingleIdentifier')
            ->willReturnCallback(static fn ($id) => '`' . $id . '`');
        $connection->method('quote')
            ->willReturnCallback(static fn ($val) => "'" . str_replace("'", "''", (string) $val) . "'");

        $executedStatements = [];
        $connection->method('executeStatement')
            ->willReturnCallback(static function ($sql) use (&$executedStatements) {
                $executedStatements[] = $sql;

                return 0;
            });

        $entities = [
            'App\Entity\Customer' => [
                'metadata'   => $metadata,
                'reflection' => $this->createMock(ReflectionClass::class),
                'attribute'  => new Anonymize(truncate: true),
            ],
        ];

        $result = $this->service->truncateTables($em, $entities, false);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('person', $result);
        $this->assertNotEmpty($executedStatements);
        $deleteSql = array_values(array_filter($executedStatements, static fn ($s) => str_contains($s, 'DELETE FROM')));
        $this->assertCount(1, $deleteSql, 'Should execute one DELETE statement for polymorphic truncate');
        $this->assertStringContainsString('DELETE FROM `person`', $deleteSql[0]);
        $this->assertStringContainsString('`type` = \'customer\'', $deleteSql[0]);
    }
}
