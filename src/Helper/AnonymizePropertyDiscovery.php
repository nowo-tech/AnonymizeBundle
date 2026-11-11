<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Helper;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Embedded;
use InvalidArgumentException;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;
use Nowo\AnonymizeBundle\Metadata\AnonymizeMetadataProviderInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

use function array_merge;
use function class_exists;
use function count;
use function explode;
use function is_array;
use function is_object;
use function is_string;
use function property_exists;
use function sprintf;
use function usort;

use const PHP_INT_MAX;

/**
 * Discovers #[AnonymizeProperty] on entity fields and on Doctrine embeddable properties, plus (since 1.5) fields
 * selected by configuration for classes without attributes.
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
     * @param ClassMetadata<object>|null $metadata Since 1.5: Doctrine metadata, so embeddables mapped in XML (no
     *                                             #[ORM\Embedded] attribute) are discovered too
     * @param array<string, AnonymizeProperty> $configured Since 1.5: fields selected without attributes (see
     *                                                     {@see AnonymizeMetadataProviderInterface}),
     *                                                     keyed by field name / embed path; an attribute on the same field wins
     *
     * @throws InvalidArgumentException when a configured field matches no property of the class or embeddable
     *
     * @return list<array{property: ReflectionProperty, attribute: AnonymizeProperty, weight: int, fieldName: string}>
     */
    public static function discover(ReflectionClass $reflection, ?ClassMetadata $metadata = null, array $configured = []): array
    {
        $properties              = [];
        $propertiesWithoutWeight = [];

        foreach ($reflection->getProperties() as $property) {
            $embeddedClass = self::resolveEmbeddedClass($property, $metadata);

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

        $discovered = [];
        foreach ([...$properties, ...$propertiesWithoutWeight] as $data) {
            $discovered[$data['fieldName']] = true;
        }

        foreach ($configured as $fieldName => $attribute) {
            if (isset($discovered[$fieldName])) {
                continue;
            }

            self::appendPropertyData(
                self::resolveConfiguredProperty($reflection, $metadata, $fieldName),
                $attribute,
                $fieldName,
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
     * The reflection property of a configured field: `field` on the class (or a parent), `embed.field` on the
     * embeddable class of `embed`.
     *
     * @param ReflectionClass<object> $reflection
     * @param ClassMetadata<object>|null $metadata
     */
    private static function resolveConfiguredProperty(ReflectionClass $reflection, ?ClassMetadata $metadata, string $fieldName): ReflectionProperty
    {
        $path  = explode('.', $fieldName, 2);
        $owner = $reflection;

        if (count($path) === 2) {
            $host          = self::findProperty($reflection, $path[0]);
            $embeddedClass = $host instanceof ReflectionProperty ? (self::resolveEmbeddedClass($host, $metadata) ?? self::declaredClass($host)) : null;
            $owner         = $embeddedClass !== null && class_exists($embeddedClass) ? new ReflectionClass($embeddedClass) : null;
        }

        $property = $owner instanceof ReflectionClass ? self::findProperty($owner, $path[count($path) - 1]) : null;
        if (!$property instanceof ReflectionProperty) {
            throw new InvalidArgumentException(sprintf('The anonymized field "%s" configured for "%s" matches no property (use the Doctrine field name, or "embedProperty.field" for an embedded value object).', $fieldName, $reflection->getName()));
        }

        return $property;
    }

    /** The class named by a property's type declaration (an embedded value object without metadata). */
    private static function declaredClass(ReflectionProperty $property): ?string
    {
        $type = $property->getType();

        return $type instanceof ReflectionNamedType && !$type->isBuiltin() ? $type->getName() : null;
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private static function findProperty(ReflectionClass $reflection, string $name): ?ReflectionProperty
    {
        for ($class = $reflection; $class instanceof ReflectionClass; $class = $class->getParentClass()) {
            if ($class->hasProperty($name)) {
                return $class->getProperty($name);
            }
        }

        return null;
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

    /**
     * @param ClassMetadata<object>|null $metadata
     */
    private static function resolveEmbeddedClass(ReflectionProperty $property, ?ClassMetadata $metadata = null): ?string
    {
        $attributes = $property->getAttributes(Embedded::class);
        if ($attributes === []) {
            // Since 1.5: an embeddable mapped in XML / PHP is known from the Doctrine metadata only.
            return $metadata instanceof ClassMetadata ? self::embeddedClassFromMetadata($metadata, $property->getName()) : null;
        }

        /** @var Embedded $embedded */
        $embedded = $attributes[0]->newInstance();
        if (is_string($embedded->class) && $embedded->class !== '') {
            return $embedded->class;
        }

        return self::declaredClass($property);
    }

    /**
     * Embedded class of a top-level embed (array mapping on ORM 2, EmbeddedClassMapping object on ORM 3).
     *
     * @param ClassMetadata<object> $metadata
     */
    private static function embeddedClassFromMetadata(ClassMetadata $metadata, string $name): ?string
    {
        $mapping = $metadata->embeddedClasses[$name] ?? null;
        if (is_array($mapping)) {
            $class = $mapping['class'] ?? null;
        } elseif (is_object($mapping) && property_exists($mapping, 'class')) {
            $class = $mapping->class;
        } else {
            return null;
        }

        return is_string($class) && $class !== '' ? $class : null;
    }
}
