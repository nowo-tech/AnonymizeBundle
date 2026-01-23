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
    ) {}

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
                'address' => 'nowo_anonymize.faker.address',
                'date' => 'nowo_anonymize.faker.date',
                'username' => 'nowo_anonymize.faker.username',
                'url' => 'nowo_anonymize.faker.url',
                'company' => 'nowo_anonymize.faker.company',
                'masking' => 'nowo_anonymize.faker.masking',
                'password' => 'nowo_anonymize.faker.password',
                'ip_address' => 'nowo_anonymize.faker.ip_address',
                'mac_address' => 'nowo_anonymize.faker.mac_address',
                'uuid' => 'nowo_anonymize.faker.uuid',
                'hash' => 'nowo_anonymize.faker.hash',
                'coordinate' => 'nowo_anonymize.faker.coordinate',
                'color' => 'nowo_anonymize.faker.color',
                'boolean' => 'nowo_anonymize.faker.boolean',
                'numeric' => 'nowo_anonymize.faker.numeric',
                'file' => 'nowo_anonymize.faker.file',
                'json' => 'nowo_anonymize.faker.json',
                'text' => 'nowo_anonymize.faker.text',
                'enum' => 'nowo_anonymize.faker.enum',
                'country' => 'nowo_anonymize.faker.country',
                'language' => 'nowo_anonymize.faker.language',
                'hash_preserve' => 'nowo_anonymize.faker.hash_preserve',
                'shuffle' => 'nowo_anonymize.faker.shuffle',
                'constant' => 'nowo_anonymize.faker.constant',
                'dni_cif' => 'nowo_anonymize.faker.dni_cif',
                'name_fallback' => 'nowo_anonymize.faker.name_fallback',
                'html' => 'nowo_anonymize.faker.html',
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
            'address' => new AddressFaker($this->locale),
            'date' => new DateFaker($this->locale),
            'username' => new UsernameFaker($this->locale),
            'url' => new UrlFaker($this->locale),
            'company' => new CompanyFaker($this->locale),
            'masking' => new MaskingFaker(),
            'password' => new PasswordFaker($this->locale),
            'ip_address' => new IpAddressFaker($this->locale),
            'mac_address' => new MacAddressFaker($this->locale),
            'uuid' => new UuidFaker($this->locale),
            'hash' => new HashFaker($this->locale),
            'coordinate' => new CoordinateFaker($this->locale),
            'color' => new ColorFaker($this->locale),
            'boolean' => new BooleanFaker($this->locale),
            'numeric' => new NumericFaker($this->locale),
            'file' => new FileFaker($this->locale),
            'json' => new JsonFaker($this->locale),
            'text' => new TextFaker($this->locale),
            'enum' => new EnumFaker($this->locale),
            'country' => new CountryFaker($this->locale),
            'language' => new LanguageFaker($this->locale),
            'hash_preserve' => new HashPreserveFaker(),
            'shuffle' => new ShuffleFaker(),
            'constant' => new ConstantFaker(),
            'dni_cif' => new DniCifFaker($this->locale),
            'name_fallback' => new NameFallbackFaker($this->locale),
            'html' => new HtmlFaker($this->locale),
            'service' => new ServiceFaker($this->container, $serviceName ?? ''),
            default => throw new \InvalidArgumentException(sprintf('Unsupported faker type: %s', $type)),
        };
    }
}
