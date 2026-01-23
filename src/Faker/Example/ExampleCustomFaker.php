<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker\Example;

use Doctrine\ORM\EntityManagerInterface;
use Nowo\AnonymizeBundle\Faker\FakerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Example Custom Faker Service.
 *
 * This is a reference implementation showing how to create a custom faker service
 * that can be used with the 'service' faker type in #[AnonymizeProperty] attributes.
 *
 * This example demonstrates:
 * - How to preserve the original value (useful for testing events)
 * - How to access other fields from the current record
 * - How to access related entities using EntityManager
 * - How to implement custom anonymization logic
 *
 * USAGE:
 * 1. Copy this file to your project (e.g., src/Service/YourCustomFaker.php)
 * 2. Change the namespace to match your project (e.g., App\Service)
 * 3. Update the class name if needed
 * 4. Implement your custom logic in the generate() method
 * 5. Register the service in services.yaml or use #[Autoconfigure(public: true)]
 * 6. Use it in your entity:
 *
 *     #[AnonymizeProperty(
 *         type: 'service',
 *         service: 'App\Service\YourCustomFaker',
 *         weight: 1,
 *         options: [
 *             'preserve_original' => true,  // Set to false to anonymize
 *             'related_entity' => 'App\Entity\RelatedEntity',
 *             'custom_option' => 'value'
 *         ]
 *     )]
 *
 * ACCESSING DATA:
 * The $options array contains:
 * - 'original_value' (mixed): The original value of the field being anonymized
 * - 'record' (array): The full database record (all fields of the current entity)
 * - Any custom options passed via the 'options' parameter in #[AnonymizeProperty]
 *
 * ACCESSING RELATED ENTITIES:
 * To access related entities, you can:
 * 1. Inject EntityManagerInterface in the constructor
 * 2. Use the 'record' array to get foreign key values
 * 3. Query related entities using the EntityManager
 *
 * EXAMPLE: Accessing a related entity
 * ```php
 * $relatedId = $options['record']['related_entity_id'] ?? null;
 * if ($relatedId && $this->entityManager !== null) {
 *     $relatedEntity = $this->entityManager->getRepository(RelatedEntity::class)->find($relatedId);
 *     // Use $relatedEntity data in your anonymization logic
 * }
 * ```
 *
 * EVENTS:
 * The bundle dispatches events that you can listen to:
 * - AnonymizePropertyEvent: Dispatched before anonymizing each property
 *   - Access via: $event->getOriginalValue(), $event->getRecord(), $event->getEntityManager()
 *   - Modify via: $event->setAnonymizedValue($newValue)
 *   - Skip via: $event->setSkipAnonymization(true)
 * - BeforeAnonymizeEvent: Dispatched before starting anonymization
 * - AfterAnonymizeEvent: Dispatched after completing anonymization
 * - BeforeEntityAnonymizeEvent: Dispatched before anonymizing an entity class
 * - AfterEntityAnonymizeEvent: Dispatched after anonymizing an entity class
 *
 * For more information, see the documentation at:
 * https://github.com/nowo-tech/anonymize-bundle
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 *
 * @example
 * // In your entity:
 * #[AnonymizeProperty(
 *     type: 'service',
 *     service: 'App\Service\ExampleCustomFaker',
 *     weight: 1,
 *     options: ['preserve_original' => true]
 * )]
 * #[ORM\Column(type: Types::STRING, length: 255)]
 * private ?string $exampleField = null;
 */
#[Autoconfigure(public: true)]
final class ExampleCustomFaker implements FakerInterface
{
    /**
     * Creates a new ExampleCustomFaker instance.
     *
     * You can inject any service you need here, such as:
     * - EntityManagerInterface: To query related entities
     * - LoggerInterface: To log anonymization actions
     * - Other services: For custom business logic
     *
     * @param EntityManagerInterface|null $entityManager Optional entity manager for accessing related entities
     */
    public function __construct(
        private ?EntityManagerInterface $entityManager = null
    ) {}

    /**
     * Generates an anonymized value.
     *
     * This method is called by the bundle for each field that uses this faker.
     * You can access:
     * - The original value via $options['original_value']
     * - The full record via $options['record']
     * - Custom options passed in #[AnonymizeProperty] via $options['custom_option_name']
     *
     * @param array<string, mixed> $options Options:
     *   - 'original_value' (mixed): The original value from the database (always provided)
     *   - 'record' (array): The full database record with all fields (always provided)
     *   - 'preserve_original' (bool): If true, return the original value unchanged (default: false)
     *   - 'related_entity' (string): Class name of a related entity to query (optional)
     *   - Any other custom options you define in #[AnonymizeProperty]
     * @return mixed The anonymized value (or original if preserve_original is true)
     */
    public function generate(array $options = []): mixed
    {
        // Get the original value
        $originalValue = $options['original_value'] ?? null;

        // Get the full record (all fields of the current entity)
        $record = $options['record'] ?? [];

        // Get custom options
        $preserveOriginal = $options['preserve_original'] ?? false;
        $relatedEntityClass = $options['related_entity'] ?? null;

        // If preserve_original is true, return the original value unchanged
        // This is useful for testing events and seeing what data is available
        if ($preserveOriginal) {
            return $originalValue;
        }

        // EXAMPLE: Access other fields from the current record
        // You can access any field from the current entity's record
        $otherField = $record['other_field'] ?? null;
        $relatedId = $record['related_entity_id'] ?? null;

        // EXAMPLE: Access related entities using EntityManager
        // This shows how to query related entities if needed
        $relatedEntityData = null;
        if ($relatedId && $relatedEntityClass && $this->entityManager !== null) {
            try {
                $relatedEntity = $this->entityManager->getRepository($relatedEntityClass)->find($relatedId);
                if ($relatedEntity !== null) {
                    // Access related entity data
                    // For example, if RelatedEntity has a 'name' field:
                    // $relatedEntityData = $relatedEntity->getName();
                }
            } catch (\Exception $e) {
                // Handle exception (e.g., entity not found, class doesn't exist)
                // In production, you might want to log this
            }
        }

        // EXAMPLE: Custom anonymization logic
        // Implement your custom logic here
        // For this example, we'll just return a simple anonymized value
        // In a real scenario, you would implement your specific anonymization rules

        // If original value is null, return null
        if ($originalValue === null) {
            return null;
        }

        // Example: Simple anonymization (replace with your logic)
        // This is just an example - implement your actual anonymization logic
        if (is_string($originalValue)) {
            // Example: Prefix with "ANONYMIZED_" and add a hash
            return 'ANONYMIZED_' . substr(md5($originalValue), 0, 8);
        }

        // For other types, return a default anonymized value
        return 'ANONYMIZED_VALUE';
    }
}
