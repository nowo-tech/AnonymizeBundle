<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Helper;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Helper class for Doctrine ORM mapping operations.
 *
 * Provides static utility methods compatible with ORM 2.13+ and 3.x,
 * avoiding deprecated ArrayAccess on FieldMapping (removed in ORM 4.0).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class OrmHelper
{
    /**
     * Resolves the database column name for a mapped field.
     *
     * @param ClassMetadata<object> $metadata The entity metadata
     * @param string $fieldName The mapped field name
     */
    public static function getFieldColumnName(ClassMetadata $metadata, string $fieldName): string
    {
        if (method_exists($metadata, 'getColumnName')) {
            $columnName = $metadata->getColumnName($fieldName);
            if (is_string($columnName) && $columnName !== '') {
                return $columnName;
            }
        }

        return self::getColumnNameFromFieldMapping($metadata->getFieldMapping($fieldName), $fieldName);
    }

    /**
     * Extracts the column name from a field mapping (object, legacy array, or null).
     *
     * @param mixed $fieldMapping FieldMapping instance, legacy array mapping, or null
     * @param string $fallback Fallback when column name cannot be resolved
     */
    public static function getColumnNameFromFieldMapping(mixed $fieldMapping, string $fallback = 'id'): string
    {
        if ($fieldMapping === null) {
            return $fallback;
        }

        if (is_object($fieldMapping)) {
            if (property_exists($fieldMapping, 'columnName')) {
                $columnName = $fieldMapping->columnName;
                if (is_string($columnName) && $columnName !== '') {
                    return $columnName;
                }
            }

            if (method_exists($fieldMapping, 'getColumnName')) {
                $columnName = $fieldMapping->getColumnName();
                if (is_string($columnName) && $columnName !== '') {
                    return $columnName;
                }
            }
        }

        if (is_array($fieldMapping)) {
            $columnName = $fieldMapping['columnName'] ?? null;
            if (is_string($columnName) && $columnName !== '') {
                return $columnName;
            }
        }

        return $fallback;
    }

    /**
     * Extracts the Doctrine field type from a field mapping (object, legacy array, or null).
     *
     * @param mixed $fieldMapping FieldMapping instance, legacy array mapping, or null
     * @param string $fallback Fallback when type cannot be resolved
     */
    public static function getFieldTypeFromFieldMapping(mixed $fieldMapping, string $fallback = 'string'): string
    {
        if ($fieldMapping === null) {
            return $fallback;
        }

        if (is_object($fieldMapping)) {
            if (property_exists($fieldMapping, 'type')) {
                $type = $fieldMapping->type;
                if (is_string($type) && $type !== '') {
                    return $type;
                }
            }

            if (method_exists($fieldMapping, 'getType')) {
                $type = $fieldMapping->getType();
                if (is_string($type) && $type !== '') {
                    return $type;
                }
            }
        }

        if (is_array($fieldMapping)) {
            $type = $fieldMapping['type'] ?? null;
            if (is_string($type) && $type !== '') {
                return $type;
            }
        }

        return $fallback;
    }
}
