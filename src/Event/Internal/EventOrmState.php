<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Event\Internal;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Nowo\AnonymizeBundle\Metadata\AnonymizeTarget;
use ReflectionClass;
use ReflectionProperty;
use WeakMap;

/**
 * Doctrine objects behind the deprecated getters of the anonymization events (since 1.6, REQ-ARCH-001 rule 3).
 *
 * The events hold scalars and {@see AnonymizeTarget} only. The entity manager, the
 * class metadata and the reflection objects the deprecated getters return live here, in a WeakMap keyed by the event:
 * a VarDumper dump, `var_export()`, `print_r()` or an `(array)` cast of an event therefore never shows the entity
 * manager (and its connection parameters, password included). Entries are collected with their event.
 *
 * @internal
 *
 * @since 1.6.0
 */
final class EventOrmState
{
    /** @var WeakMap<object, array{em: EntityManagerInterface|null, metadata: ClassMetadata<object>|null, reflection: ReflectionClass<object>|null, property: ReflectionProperty|null}>|null */
    private static ?WeakMap $states = null; // @phpstan-ignore frankenphp.worker.noMutableStaticProperty (WeakMap keyed by the per-run event)

    /**
     * @param ClassMetadata<object>|null $metadata
     * @param ReflectionClass<object>|null $reflection
     */
    public static function attach(
        object $event,
        ?EntityManagerInterface $entityManager,
        ?ClassMetadata $metadata = null,
        ?ReflectionClass $reflection = null,
        ?ReflectionProperty $property = null,
    ): void {
        // @igor-ignore - WeakMap keyed by the per-run event; entries are collected with it.
        self::$states ??= new WeakMap(); // @phpstan-ignore frankenphp.worker.noMutableStaticProperty
        // @igor-ignore - WeakMap keyed by the per-run event; entries are collected with it.
        self::$states[$event] = ['em' => $entityManager, 'metadata' => $metadata, 'reflection' => $reflection, 'property' => $property]; // @phpstan-ignore frankenphp.worker.noMutableStaticProperty
    }

    public static function entityManager(object $event): ?EntityManagerInterface
    {
        return self::$states[$event]['em'] ?? null;
    }

    /**
     * @return ClassMetadata<object>|null
     */
    public static function metadata(object $event): ?ClassMetadata
    {
        return self::$states[$event]['metadata'] ?? null;
    }

    /**
     * @return ReflectionClass<object>|null
     */
    public static function reflection(object $event): ?ReflectionClass
    {
        return self::$states[$event]['reflection'] ?? null;
    }

    public static function property(object $event): ?ReflectionProperty
    {
        return self::$states[$event]['property'] ?? null;
    }
}
