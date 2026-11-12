<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Internal;

use Doctrine\ORM\Mapping\ClassMetadata;
use Nowo\AnonymizeBundle\Helper\OrmHelper;
use Nowo\AnonymizeBundle\Metadata\AnonymizeTarget;

/**
 * Builds the ORM-free {@see AnonymizeTarget} of a class from its Doctrine metadata (since 1.6).
 *
 * @internal
 *
 * @since 1.6.0
 */
final class AnonymizeTargetFactory
{
    /**
     * @param ClassMetadata<object> $metadata
     */
    public static function fromMetadata(ClassMetadata $metadata): AnonymizeTarget
    {
        $columns = [];
        foreach ($metadata->getFieldNames() as $fieldName) {
            $columns[$fieldName] = OrmHelper::getFieldColumnName($metadata, $fieldName);
        }
        $discriminator = self::discriminator($metadata);

        return new AnonymizeTarget(
            $metadata->getName(),
            $metadata->getTableName(),
            array_values($metadata->getIdentifierColumnNames()),
            $columns,
            $discriminator['column'],
            $discriminator['value'],
        );
    }

    /**
     * Discriminator column and value of a class in an inheritance hierarchy (both null otherwise).
     *
     * Uses public metadata properties for compatibility with Doctrine ORM 2.x and 3.x.
     *
     * @param ClassMetadata<object> $metadata
     *
     * @return array{column: string|null, value: string|null}
     */
    public static function discriminator(ClassMetadata $metadata): array
    {
        $inheritanceType = $metadata->inheritanceType;
        if ($inheritanceType === ClassMetadata::INHERITANCE_TYPE_NONE) {
            return ['column' => null, 'value' => null];
        }
        $discValue = $metadata->discriminatorValue ?? null;
        if ($discValue === null) {
            return ['column' => null, 'value' => null];
        }
        // DiscriminatorColumnMapping object (ORM 3) or legacy array mapping (ORM 2)
        $columnName = OrmHelper::resolveDiscriminatorColumnName($metadata->discriminatorColumn ?? null);
        if ($columnName === null || $columnName === '') {
            return ['column' => null, 'value' => null];
        }

        return ['column' => $columnName, 'value' => (string) $discValue];
    }
}
