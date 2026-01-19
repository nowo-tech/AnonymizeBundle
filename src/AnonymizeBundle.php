<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle;

use Nowo\AnonymizeBundle\DependencyInjection\AnonymizeExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony bundle for database anonymization functionality.
 *
 * This bundle provides a complete solution for anonymizing database records in Symfony applications.
 * It allows entities to be marked with attributes for automatic anonymization using Faker generators.
 *
 * ⚠️ **IMPORTANT**: This bundle is **development-only** and should **never** be installed or used
 * in production environments. The command includes built-in protection that prevents execution
 * outside of dev/test environments.
 *
 * Features:
 * - Attribute-based anonymization configuration
 * - Support for multiple Doctrine connections
 * - Multiple faker types (email, name, surname, age, phone, IBAN, credit card, custom service)
 * - Weight-based anonymization order
 * - Pattern-based inclusion/exclusion filters
 * - Support for MySQL, PostgreSQL (MongoDB coming soon)
 * - Automatic environment validation (dev/test only)
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class AnonymizeBundle extends Bundle
{
    /**
     * Overridden to allow for the custom extension alias.
     *
     * Creates and returns the container extension instance if not already created.
     * The extension is cached after the first call to ensure the same instance is returned
     * on subsequent calls.
     *
     * @return ExtensionInterface|null The container extension instance, or null if not available
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new AnonymizeExtension();
        }

        return $this->extension;
    }
}
