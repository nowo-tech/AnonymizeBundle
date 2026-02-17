<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use Nowo\AnonymizeBundle\Service\SchemaService;
use PHPUnit\Framework\TestCase;

/**
 * Test case for SchemaService.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class SchemaServiceTest extends TestCase
{
    private SchemaService $service;

    protected function setUp(): void
    {
        $this->service = new SchemaService();
    }

    /**
     * Test that hasAnonymizedColumn returns true when column exists.
     */
    public function testHasAnonymizedColumnReturnsTrueWhenColumnExists(): void
    {
        $column = $this->createMock(Column::class);
        $column->method('getName')->willReturn('anonymized');

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->willReturn(true);
        $schemaManager->method('listTableColumns')->willReturn([$column]);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('users');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($metadata);
        $em->method('getConnection')->willReturn($connection);

        $result = $this->service->hasAnonymizedColumn($em, 'App\Entity\User');

        $this->assertTrue($result);
    }

    /**
     * Test that hasAnonymizedColumn returns false when column doesn't exist.
     */
    public function testHasAnonymizedColumnReturnsFalseWhenColumnDoesNotExist(): void
    {
        $column = $this->createMock(Column::class);
        $column->method('getName')->willReturn('email');

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->willReturn(true);
        $schemaManager->method('listTableColumns')->willReturn([$column]);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('users');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($metadata);
        $em->method('getConnection')->willReturn($connection);

        $result = $this->service->hasAnonymizedColumn($em, 'App\Entity\User');

        $this->assertFalse($result);
    }

    /**
     * Test that hasAnonymizedColumn returns false when table doesn't exist.
     */
    public function testHasAnonymizedColumnReturnsFalseWhenTableDoesNotExist(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->willReturn(false);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('users');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($metadata);
        $em->method('getConnection')->willReturn($connection);

        $result = $this->service->hasAnonymizedColumn($em, 'App\Entity\User');

        $this->assertFalse($result);
    }

    /**
     * Test that hasAnonymizedColumn returns false on exception.
     */
    public function testHasAnonymizedColumnReturnsFalseOnException(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willThrowException(new Exception('Test exception'));

        $result = $this->service->hasAnonymizedColumn($em, 'App\Entity\User');

        $this->assertFalse($result);
    }

    /**
     * Test that hasColumn returns true when column exists.
     */
    public function testHasColumnReturnsTrueWhenColumnExists(): void
    {
        $column = $this->createMock(Column::class);
        $column->method('getName')->willReturn('email');

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->willReturn(true);
        $schemaManager->method('listTableColumns')->willReturn([$column]);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('users');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($metadata);
        $em->method('getConnection')->willReturn($connection);

        $result = $this->service->hasColumn($em, 'App\Entity\User', 'email');

        $this->assertTrue($result);
    }

    /**
     * Test that hasColumn returns false when column doesn't exist.
     */
    public function testHasColumnReturnsFalseWhenColumnDoesNotExist(): void
    {
        $column = $this->createMock(Column::class);
        $column->method('getName')->willReturn('email');

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->willReturn(true);
        $schemaManager->method('listTableColumns')->willReturn([$column]);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('users');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($metadata);
        $em->method('getConnection')->willReturn($connection);

        $result = $this->service->hasColumn($em, 'App\Entity\User', 'phone');

        $this->assertFalse($result);
    }

    /**
     * Test that hasColumn returns false on exception.
     */
    public function testHasColumnReturnsFalseOnException(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willThrowException(new Exception('Test exception'));

        $result = $this->service->hasColumn($em, 'App\Entity\User', 'email');

        $this->assertFalse($result);
    }

    /**
     * Test that hasColumn returns false when table doesn't exist.
     */
    public function testHasColumnReturnsFalseWhenTableDoesNotExist(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->willReturn(false);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('users');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($metadata);
        $em->method('getConnection')->willReturn($connection);

        $result = $this->service->hasColumn($em, 'App\Entity\User', 'email');

        $this->assertFalse($result);
    }
}
