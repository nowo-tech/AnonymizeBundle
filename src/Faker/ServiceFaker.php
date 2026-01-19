<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use Psr\Container\ContainerInterface;

/**
 * Faker for generating anonymized values using a custom service.
 *
 * The service must implement FakerInterface or have a method that returns
 * an anonymized value.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
final class ServiceFaker implements FakerInterface
{
    /**
     * Creates a new ServiceFaker instance.
     *
     * @param ContainerInterface $container The service container
     * @param string $serviceName The name of the service to use for anonymization
     */
    public function __construct(
        private ContainerInterface $container,
        private string $serviceName
    ) {
    }

    /**
     * Generates an anonymized value using the configured service.
     *
     * @param array<string, mixed> $options Additional options passed to the service
     * @return mixed The anonymized value
     * @throws \RuntimeException If the service is not found or doesn't implement FakerInterface
     */
    public function generate(array $options = []): mixed
    {
        if (!$this->container->has($this->serviceName)) {
            throw new \RuntimeException(sprintf('Service "%s" not found.', $this->serviceName));
        }

        $service = $this->container->get($this->serviceName);

        if ($service instanceof FakerInterface) {
            return $service->generate($options);
        }

        if (method_exists($service, 'generate')) {
            return $service->generate($options);
        }

        if (method_exists($service, '__invoke')) {
            return $service($options);
        }

        throw new \RuntimeException(
            sprintf(
                'Service "%s" must implement FakerInterface, have a generate() method, or be callable.',
                $this->serviceName
            )
        );
    }
}
