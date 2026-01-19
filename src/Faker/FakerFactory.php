<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Nowo\AnonymizeBundle\Enum\FakerType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Factory for creating faker instances.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class FakerFactory
{
    /**
     * Creates a new FakerFactory instance.
     *
     * @param string $locale The locale for Faker generators (default: 'en_US')
     * @param ContainerInterface|null $container The service container for service-based fakers
     */
    public function __construct(
        private string $locale = 'en_US',
        private ?ContainerInterface $container = null
    ) {}

    /**
     * Creates a faker instance for the given type.
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
