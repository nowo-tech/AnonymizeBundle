<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * Service for checking database schema information.
 *
 * This service provides utilities for checking if columns exist in database tables,
 * which is useful for optional features like the anonymized column tracking.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class SchemaService
{
    /**
     * Checks if the anonymized column exists in the database table for the given entity.
     *
     * @param EntityManagerInterface $em The entity manager
     * @param string $entityClass The fully qualified entity class name
     *
     * @return bool True if the anonymized column exists, false otherwise
     */
    public function hasAnonymizedColumn(EntityManagerInterface $em, string $entityClass): bool
    {
        try {
            $metadata      = $em->getClassMetadata($entityClass);
            $tableName     = $metadata->getTableName();
            $connection    = $em->getConnection();
            $schemaManager = $connection->createSchemaManager();

            if (!$schemaManager->tablesExist([$tableName])) {
                return false;
            }

            $columns = $schemaManager->listTableColumns($tableName);
            foreach ($columns as $column) {
                if ($column->getName() === 'anonymized') {
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Checks if a specific column exists in the database table for the given entity.
     *
     * @param EntityManagerInterface $em The entity manager
     * @param string $entityClass The fully qualified entity class name
     * @param string $columnName The column name to check
     *
     * @return bool True if the column exists, false otherwise
     */
    public function hasColumn(EntityManagerInterface $em, string $entityClass, string $columnName): bool
    {
        try {
            $metadata      = $em->getClassMetadata($entityClass);
            $tableName     = $metadata->getTableName();
            $connection    = $em->getConnection();
            $schemaManager = $connection->createSchemaManager();

            if (!$schemaManager->tablesExist([$tableName])) {
                return false;
            }

            $columns = $schemaManager->listTableColumns($tableName);
            foreach ($columns as $column) {
                if ($column->getName() === $columnName) {
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}
