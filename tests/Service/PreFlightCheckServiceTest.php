<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Faker\FakerFactory;
use Nowo\AnonymizeBundle\Service\PreFlightCheckService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test case for PreFlightCheckService.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class PreFlightCheckServiceTest extends TestCase
{
    private FakerFactory $fakerFactory;
    private PreFlightCheckService $service;

    protected function setUp(): void
    {
        $this->fakerFactory = new FakerFactory('en_US');
        $this->service      = new PreFlightCheckService($this->fakerFactory);
    }

    /**
     * Test that valid entities pass pre-flight checks.
     */
    public function testValidEntitiesPassChecks(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        // getFieldMapping returns FieldMapping which implements ArrayAccess
        // Create a real FieldMapping instance with the columnName property
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->with(['test_table'])
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->with('test_table')
            ->willReturn(['test_column' => $column]);

        $reflection = $this->createMock(ReflectionClass::class);
        $reflection->method('getProperties')
            ->willReturn([]);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertEmpty($errors);
    }

    /**
     * Test that database connectivity check fails when connection fails.
     */
    public function testDatabaseConnectivityCheckFails(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willThrowException(new Exception('Connection failed'));

        $entities = [];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Database connectivity check failed', $errors[0]);
    }

    /**
     * Test that missing table is detected.
     */
    public function testMissingTableIsDetected(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('missing_table');

        $schemaManager->method('tablesExist')
            ->with(['missing_table'])
            ->willReturn(false);

        $reflection = $this->createMock(ReflectionClass::class);
        $reflection->method('getProperties')
            ->willReturn([]);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('does not exist in database', $errors[0]);
    }

    /**
     * Test that invalid faker type is detected.
     */
    public function testInvalidFakerTypeIsDetected(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        // getFieldMapping returns FieldMapping which implements ArrayAccess
        // Create a real FieldMapping instance with the columnName property
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->with(['test_table'])
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->with('test_table')
            ->willReturn(['test_column' => $column]);

        // Create a real reflection class with a property that has an invalid attribute
        $testEntity = new class {
            #[AnonymizeProperty(type: 'invalid_faker_type', weight: 1)]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid faker type', $errors[0]);
    }

    /**
     * Test that service faker without service name is detected.
     */
    public function testServiceFakerWithoutServiceNameIsDetected(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        // getFieldMapping returns FieldMapping which implements ArrayAccess
        // Create a real FieldMapping instance with the columnName property
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->with(['test_table'])
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->with('test_table')
            ->willReturn(['test_column' => $column]);

        // Create a real reflection class with a property that has service type without service name
        $testEntity = new class {
            #[AnonymizeProperty(type: 'service', weight: 1, service: '')]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Faker type "service" requires a "service" option', $errors[0]);
    }

    /**
     * Test that mapped superclass is skipped.
     */
    public function testMappedSuperclassIsSkipped(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = true;
        $metadata->isEmbeddedClass    = false;

        $reflection = $this->createMock(ReflectionClass::class);
        $reflection->method('getProperties')
            ->willReturn([]);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        // Should pass because mapped superclass is skipped
        $this->assertEmpty($errors);
    }

    /**
     * Test that missing column is detected.
     */
    public function testMissingColumnIsDetected(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('missing_column', 'string', 'missing_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->with(['test_table'])
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('other_column');

        $schemaManager->method('listTableColumns')
            ->with('test_table')
            ->willReturn(['other_column' => $column]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1)]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('does not exist in table', $errors[0]);
    }

    /**
     * Test that invalid include pattern is detected.
     */
    public function testInvalidIncludePatternIsDetected(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        // getFieldMapping returns FieldMapping which implements ArrayAccess
        // Create a real FieldMapping instance with the columnName property
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->with(['test_table'])
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->with('test_table')
            ->willReturn(['test_column' => $column]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1, includePatterns: ['field' => ''])]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid include pattern', $errors[0]);
    }

    /**
     * Test that invalid exclude pattern is detected.
     */
    public function testInvalidExcludePatternIsDetected(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        // getFieldMapping returns FieldMapping which implements ArrayAccess
        // Create a real FieldMapping instance with the columnName property
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->with(['test_table'])
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->with('test_table')
            ->willReturn(['test_column' => $column]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1, excludePatterns: ['' => 'pattern'])]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid exclude pattern', $errors[0]);
    }

    /**
     * Test that embedded class is skipped.
     */
    public function testEmbeddedClassIsSkipped(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = true;

        $reflection = $this->createMock(ReflectionClass::class);
        $reflection->method('getProperties')
            ->willReturn([]);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        // Should pass because embedded class is skipped
        $this->assertEmpty($errors);
    }

    /**
     * Test that checkEntityExistence handles exceptions gracefully.
     */
    public function testCheckEntityExistenceHandlesExceptions(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');

        $schemaManager->method('tablesExist')
            ->willThrowException(new Exception('Schema error'));

        $reflection = $this->createMock(ReflectionClass::class);
        $reflection->method('getProperties')
            ->willReturn([]);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Could not check table existence', $errors[0]);
    }

    /**
     * Test that checkColumnExistence handles exceptions gracefully.
     */
    public function testCheckColumnExistenceHandlesExceptions(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(true);
        $schemaManager->method('listTableColumns')
            ->willThrowException(new Exception('Column check error'));

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1)]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        // Should not fail because column check exceptions are caught
        $this->assertIsArray($errors);
    }

    /**
     * Test that validatePatterns handles valid patterns.
     */
    public function testValidatePatternsHandlesValidPatterns(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->willReturn(['test_column' => $column]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1, includePatterns: ['id' => '>100'])]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertEmpty($errors);
    }

    /**
     * Test that validatePatterns handles valid exclude patterns.
     */
    public function testValidatePatternsHandlesValidExcludePatterns(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->willReturn(['test_column' => $column]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1, excludePatterns: ['status' => 'deleted'])]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertEmpty($errors);
    }

    /**
     * Test that validatePatterns accepts valid exclude patterns as list of configs (multiple configs).
     */
    public function testValidatePatternsHandlesValidExcludePatternsListOfConfigs(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->willReturn(['test_column' => $column]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1, excludePatterns: [
                ['role' => 'admin'],
                ['status' => 'deleted'],
            ])]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertEmpty($errors);
    }

    /**
     * Test that validatePatterns accepts valid exclude patterns with array value (OR for one field).
     */
    public function testValidatePatternsHandlesValidExcludePatternsArrayValue(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->willReturn(['test_column' => $column]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1, excludePatterns: ['email' => ['%@nowo.tech', 'operador@example.com']])]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertEmpty($errors);
    }

    /**
     * Test that validateFakerType handles enum faker with values option.
     */
    public function testValidateFakerTypeHandlesEnumFaker(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->willReturn(['test_column' => $column]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'enum', weight: 1, options: ['values' => ['active', 'inactive']])]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertEmpty($errors);
    }

    /**
     * Test that checkColumnExistence detects missing column with case-insensitive match suggestion.
     */
    public function testCheckColumnExistenceDetectsMissingColumnWithCaseInsensitiveMatch(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('testColumn', 'string', 'testColumn');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('testcolumn'); // Different case

        $schemaManager->method('listTableColumns')
            ->willReturn(['testcolumn' => $column]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1)]
            private string $testColumn;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('case mismatch', $errors[0]);
    }

    /**
     * Test that checkColumnExistence shows available columns when column is missing.
     */
    public function testCheckColumnExistenceShowsAvailableColumns(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('missing_column', 'string', 'missing_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(true);

        $column1 = $this->createMock(Column::class);
        $column1->method('getName')
            ->willReturn('id');
        $column2 = $this->createMock(Column::class);
        $column2->method('getName')
            ->willReturn('name');
        $column3 = $this->createMock(Column::class);
        $column3->method('getName')
            ->willReturn('email');

        $schemaManager->method('listTableColumns')
            ->willReturn([
                'id'    => $column1,
                'name'  => $column2,
                'email' => $column3,
            ]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1)]
            private string $missingColumn;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Available columns', $errors[0]);
    }

    /**
     * Test that checkColumnExistence limits available columns to 10.
     */
    public function testCheckColumnExistenceLimitsAvailableColumns(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('missing_column', 'string', 'missing_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(true);

        $columns = [];
        for ($i = 1; $i <= 15; ++$i) {
            $column = $this->createMock(Column::class);
            $column->method('getName')
                ->willReturn("column_{$i}");
            $columns["column_{$i}"] = $column;
        }

        $schemaManager->method('listTableColumns')
            ->willReturn($columns);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1)]
            private string $missingColumn;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('... and 5 more', $errors[0]);
    }

    /**
     * Test that validatePatterns detects invalid include patterns with empty field.
     */
    public function testValidatePatternsDetectsInvalidIncludePatternWithEmptyField(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->willReturn(['test_column' => $column]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1, includePatterns: ['' => '>100'])]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid include pattern', $errors[0]);
    }

    /**
     * Test that validatePatterns detects invalid include patterns with empty pattern.
     */
    public function testValidatePatternsDetectsInvalidIncludePatternWithEmptyPattern(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->willReturn(['test_column' => $column]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1, includePatterns: ['id' => ''])]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid include pattern', $errors[0]);
    }

    /**
     * Test that validatePatterns detects invalid exclude patterns with empty field.
     */
    public function testValidatePatternsDetectsInvalidExcludePatternWithEmptyField(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->willReturn(['test_column' => $column]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1, excludePatterns: ['' => 'deleted'])]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid exclude pattern', $errors[0]);
    }

    /**
     * Test that validatePatterns detects invalid exclude patterns with empty pattern.
     */
    public function testValidatePatternsDetectsInvalidExcludePatternWithEmptyPattern(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->willReturn(['test_column' => $column]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1, excludePatterns: ['status' => ''])]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid exclude pattern', $errors[0]);
    }

    /**
     * Test that validateFakerType detects service type without service name.
     */
    public function testValidateFakerTypeDetectsServiceTypeWithoutServiceName(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(true);

        $column = $this->createMock(Column::class);
        $column->method('getName')
            ->willReturn('test_column');

        $schemaManager->method('listTableColumns')
            ->willReturn(['test_column' => $column]);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'service', weight: 1)]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Faker type "service" requires a "service" option', $errors[0]);
    }

    /**
     * Test that checkEntityExistence skips check for mapped superclass.
     */
    public function testCheckEntityExistenceSkipsMappedSuperclass(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = true;
        $metadata->isEmbeddedClass    = false;

        $reflection = $this->createMock(ReflectionClass::class);
        $reflection->method('getProperties')
            ->willReturn([]);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertEmpty($errors);
    }

    /**
     * Test that checkEntityExistence skips check for embedded class.
     */
    public function testCheckEntityExistenceSkipsEmbeddedClass(): void
    {
        $em         = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = true;

        $reflection = $this->createMock(ReflectionClass::class);
        $reflection->method('getProperties')
            ->willReturn([]);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        $this->assertEmpty($errors);
    }

    /**
     * Test that checkColumnExistence skips check when table does not exist.
     */
    public function testCheckColumnExistenceSkipsWhenTableDoesNotExist(): void
    {
        $em            = $this->createMock(EntityManagerInterface::class);
        $connection    = $this->createMock(Connection::class);
        $schemaManager = $this->createMock(AbstractSchemaManager::class);

        $em->method('getConnection')
            ->willReturn($connection);

        $connection->method('executeQuery')
            ->with('SELECT 1')
            ->willReturn($this->createMock(\Doctrine\DBAL\Result::class));

        $connection->method('createSchemaManager')
            ->willReturn($schemaManager);

        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->isMappedSuperclass = false;
        $metadata->isEmbeddedClass    = false;
        $metadata->method('getTableName')
            ->willReturn('test_table');
        $metadata->method('hasField')
            ->willReturn(true);
        $fieldMapping = new \Doctrine\ORM\Mapping\FieldMapping('test_column', 'string', 'test_column');
        $metadata->method('getFieldMapping')
            ->willReturn($fieldMapping);

        $schemaManager->method('tablesExist')
            ->willReturn(false);

        $testEntity = new class {
            #[AnonymizeProperty(type: 'email', weight: 1)]
            private string $testProperty;
        };
        $reflection = new ReflectionClass($testEntity);

        $attribute = new Anonymize();

        $entities = [
            'TestEntity' => [
                'metadata'   => $metadata,
                'reflection' => $reflection,
                'attribute'  => $attribute,
            ],
        ];

        $errors = $this->service->performChecks($em, $entities);
        // Should have error for missing table, but not for column (since table doesn't exist)
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('does not exist in database', $errors[0]);
    }
}
