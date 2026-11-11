<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Metadata;

use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

/**
 * Selection of what is anonymized for classes that carry no #[Anonymize] / #[AnonymizeProperty] attribute (since 1.5),
 * e.g. the plain-PHP, XML-mapped domain of a DDD / hexagonal host.
 *
 * The default implementation {@see ConfigAnonymizeMetadataProvider} reads `nowo_anonymize.entities`. Alias this
 * interface to your own service to read the selection from elsewhere. The attribute classes are used as plain value
 * objects. Attributes on the class win: a class-level #[Anonymize] replaces the provider's entity settings, and an
 * #[AnonymizeProperty] replaces the provider's entry for the same field.
 *
 * Implementations must be stateless between runs (FrankenPHP worker mode without kernel reset).
 *
 * @since 1.5.0
 */
interface AnonymizeMetadataProviderInterface
{
    /**
     * Entity-level settings when the class is selected, null when it is not.
     *
     * @param class-string $className
     */
    public function getEntity(string $className): ?Anonymize;

    /**
     * Fields to anonymize, keyed by Doctrine field name; an embedded value object field uses the Doctrine path
     * `{embedProperty}.{nestedProperty}` (e.g. `address.street`).
     *
     * @param class-string $className
     *
     * @return array<string, AnonymizeProperty>
     */
    public function getProperties(string $className): array;

    /**
     * Whether the run sets the table's `anonymized` column (when the table has it) for a class that does not use
     * AnonymizableTrait.
     *
     * @param class-string $className
     */
    public function marksAnonymized(string $className): bool;
}
