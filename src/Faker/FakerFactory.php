<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Nowo\AnonymizeBundle\Enum\FakerType;
use Psr\Container\ContainerInterface;

/**
 * Factory for creating faker instances.
 *
 * This factory uses services from the container when available, falling back
 * to direct instantiation for service-based fakers or when container is not available.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class FakerFactory
{
    /**
     * Creates a new FakerFactory instance.
     *
     * @param string $locale The locale for Faker generators (fallback when container is not available)
     * @param ContainerInterface|null $container The service container for accessing faker services
     */
    public function __construct(
        private string $locale = 'en_US',
        private ?ContainerInterface $container = null
    ) {
    }

    /**
     * Creates a faker instance for the given type.
     *
     * Tries to get the faker from the service container first, then falls back
     * to direct instantiation if the container is not available.
     *
     * @param FakerType|string $type The faker type
     * @param string|null $serviceName The service name if type is 'service'
     * @return FakerInterface The faker instance
     * @throws \InvalidArgumentException If the type is not supported
     */
    public function create(FakerType|string $type, ?string $serviceName = null): FakerInterface
    {
        if ($type instanceof FakerType) {
            $type = $type->value;
        }

        // Try to get from container first (if available)
        if ($this->container !== null) {
            $serviceId = match ($type) {
                'email' => 'nowo_anonymize.faker.email',
                'name' => 'nowo_anonymize.faker.name',
                'surname' => 'nowo_anonymize.faker.surname',
                'age' => 'nowo_anonymize.faker.age',
                'phone' => 'nowo_anonymize.faker.phone',
                'iban' => 'nowo_anonymize.faker.iban',
                'credit_card' => 'nowo_anonymize.faker.credit_card',
                default => null,
            };

            if ($serviceId !== null && $this->container->has($serviceId)) {
                return $this->container->get($serviceId);
            }
        }

        // Fallback to direct instantiation
        return match ($type) {
            'email' => new EmailFaker($this->locale),
            'name' => new NameFaker($this->locale),
            'surname' => new SurnameFaker($this->locale),
            'age' => new AgeFaker($this->locale),
            'phone' => new PhoneFaker($this->locale),
            'iban' => new IbanFaker($this->locale),
            'credit_card' => new CreditCardFaker($this->locale),
            'service' => new ServiceFaker($this->container, $serviceName ?? ''),
            default => throw new \InvalidArgumentException(sprintf('Unsupported faker type: %s', $type)),
        };
    }
}
