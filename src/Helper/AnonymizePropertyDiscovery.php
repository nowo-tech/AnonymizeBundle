<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Helper;

use Doctrine\ORM\Mapping\Embedded;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

use function array_merge;
use function is_string;
use function usort;

use const PHP_INT_MAX;

/**
 * Discovers #[AnonymizeProperty] on entity fields and on Doctrine embeddable properties.
 *
 * Embed paths use Doctrine field naming: `{embedProperty}.{nestedProperty}`
 * (e.g. `phoneNumber.number` → column `phone_number` with columnPrefix `phone_`).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class AnonymizePropertyDiscovery
{
    /**
     * @param ReflectionClass<object> $reflection Entity (or mapped superclass) reflection
     *
     * @return list<array{property: ReflectionProperty, attribute: AnonymizeProperty, weight: int, fieldName: string}>
     */
    public static function discover(ReflectionClass $reflection): array
    {
        $properties              = [];
        $propertiesWithoutWeight = [];

        foreach ($reflection->getProperties() as $property) {
            $embeddedClass = self::resolveEmbeddedClass($property);

            if ($embeddedClass !== null) {
                self::collectEmbeddedProperties(
                    $property,
                    $embeddedClass,
                    $properties,
                    $propertiesWithoutWeight,
                );
                continue;
            }

            $attributes = $property->getAttributes(AnonymizeProperty::class);
            if ($attributes === []) {
                continue;
            }

            self::appendPropertyData(
                $property,
                $attributes[0]->newInstance(),
                $property->getName(),
                $properties,
                $propertiesWithoutWeight,
            );
        }

        usort($properties, static fn (array $a, array $b): int => $a['weight'] <=> $b['weight']);
        usort(
            $propertiesWithoutWeight,
            static fn (array $a, array $b): int => $a['fieldName'] <=> $b['fieldName'],
        );

        return array_merge($properties, $propertiesWithoutWeight);
    }

    /**
     * @param list<array{property: ReflectionProperty, attribute: AnonymizeProperty, weight: int, fieldName: string}> $properties
     * @param list<array{property: ReflectionProperty, attribute: AnonymizeProperty, weight: int, fieldName: string}> $propertiesWithoutWeight
     */
    private static function collectEmbeddedProperties(
        ReflectionProperty $hostProperty,
        string $embeddedClass,
        array &$properties,
        array &$propertiesWithoutWeight,
    ): void {
        if (!class_exists($embeddedClass)) {
            return;
        }

        /** @var class-string $embeddedClass */
        $embedReflection = new ReflectionClass($embeddedClass);
        $hostName        = $hostProperty->getName();

        foreach ($embedReflection->getProperties() as $embedProperty) {
            $attributes = $embedProperty->getAttributes(AnonymizeProperty::class);
            if ($attributes === []) {
                continue;
            }

            self::appendPropertyData(
                $embedProperty,
                $attributes[0]->newInstance(),
                $hostName . '.' . $embedProperty->getName(),
                $properties,
                $propertiesWithoutWeight,
            );
        }
    }

    /**
     * @param list<array{property: ReflectionProperty, attribute: AnonymizeProperty, weight: int, fieldName: string}> $properties
     * @param list<array{property: ReflectionProperty, attribute: AnonymizeProperty, weight: int, fieldName: string}> $propertiesWithoutWeight
     */
    private static function appendPropertyData(
        ReflectionProperty $property,
        AnonymizeProperty $attribute,
        string $fieldName,
        array &$properties,
        array &$propertiesWithoutWeight,
    ): void {
        $weight = $attribute->weight ?? PHP_INT_MAX;
        $data   = [
            'property'  => $property,
            'attribute' => $attribute,
            'weight'    => $weight,
            'fieldName' => $fieldName,
        ];

        if ($weight === PHP_INT_MAX) {
            $propertiesWithoutWeight[] = $data;
        } else {
            $properties[] = $data;
        }
    }

    private static function resolveEmbeddedClass(ReflectionProperty $property): ?string
    {
        $attributes = $property->getAttributes(Embedded::class);
        if ($attributes === []) {
            return null;
        }

        /** @var Embedded $embedded */
        $embedded = $attributes[0]->newInstance();
        if (is_string($embedded->class) && $embedded->class !== '') {
            return $embedded->class;
        }

        $type = $property->getType();
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $type->getName();
        }

        return null;
    }
}
