<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Faker;

use InvalidArgumentException;
use Nowo\AnonymizeBundle\Enum\FakerType;
use Psr\Container\ContainerInterface;
// use Symfony\Component\DependencyInjection\Attribute\AsAlias;
// use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function sprintf;

/**
 * Factory for creating faker instances.
 *
 * This factory uses services from the container when available, falling back
 * to direct instantiation for service-based fakers or when container is not available.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
// #[AsAlias(id: self::SERVICE_NAME)]
final class FakerFactory
{
    public const SERVICE_NAME = 'nowo_anonymize.faker_factory';

    /**
     * Creates a new FakerFactory instance.
     *
     * @param string $locale The locale for Faker generators (fallback when container is not available)
     * @param ContainerInterface|null $container The service container for accessing faker services
     */
    public function __construct(
        // #[Autowire('%nowo_anonymize.locale%')]
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
     *
     * @throws InvalidArgumentException If the type is not supported
     *
     * @return FakerInterface The faker instance
     */
    public function create(FakerType|string $type, ?string $serviceName = null): FakerInterface
    {
        if ($type instanceof FakerType) {
            $type = $type->value;
        }

        // Try to get from container first (if available)
        if ($this->container !== null) {
            $serviceId = match ($type) {
                FakerType::EMAIL->value         => 'nowo_anonymize.faker.email',
                FakerType::NAME->value          => 'nowo_anonymize.faker.name',
                FakerType::SURNAME->value       => 'nowo_anonymize.faker.surname',
                FakerType::AGE->value           => 'nowo_anonymize.faker.age',
                FakerType::PHONE->value         => 'nowo_anonymize.faker.phone',
                FakerType::IBAN->value          => 'nowo_anonymize.faker.iban',
                FakerType::CREDIT_CARD->value   => 'nowo_anonymize.faker.credit_card',
                FakerType::ADDRESS->value       => 'nowo_anonymize.faker.address',
                FakerType::DATE->value          => 'nowo_anonymize.faker.date',
                FakerType::USERNAME->value      => 'nowo_anonymize.faker.username',
                FakerType::URL->value           => 'nowo_anonymize.faker.url',
                FakerType::COMPANY->value       => 'nowo_anonymize.faker.company',
                FakerType::MASKING->value       => 'nowo_anonymize.faker.masking',
                FakerType::PASSWORD->value      => 'nowo_anonymize.faker.password',
                FakerType::IP_ADDRESS->value    => 'nowo_anonymize.faker.ip_address',
                FakerType::MAC_ADDRESS->value   => 'nowo_anonymize.faker.mac_address',
                FakerType::UUID->value          => 'nowo_anonymize.faker.uuid',
                FakerType::HASH->value          => 'nowo_anonymize.faker.hash',
                FakerType::COORDINATE->value    => 'nowo_anonymize.faker.coordinate',
                FakerType::COLOR->value         => 'nowo_anonymize.faker.color',
                FakerType::BOOLEAN->value       => 'nowo_anonymize.faker.boolean',
                FakerType::NUMERIC->value       => 'nowo_anonymize.faker.numeric',
                FakerType::FILE->value          => 'nowo_anonymize.faker.file',
                FakerType::JSON->value          => 'nowo_anonymize.faker.json',
                FakerType::TEXT->value          => 'nowo_anonymize.faker.text',
                FakerType::ENUM->value          => 'nowo_anonymize.faker.enum',
                FakerType::COUNTRY->value       => 'nowo_anonymize.faker.country',
                FakerType::LANGUAGE->value      => 'nowo_anonymize.faker.language',
                FakerType::HASH_PRESERVE->value => 'nowo_anonymize.faker.hash_preserve',
                FakerType::SHUFFLE->value       => 'nowo_anonymize.faker.shuffle',
                FakerType::CONSTANT->value      => 'nowo_anonymize.faker.constant',
                FakerType::DNI_CIF->value       => 'nowo_anonymize.faker.dni_cif',
                FakerType::NAME_FALLBACK->value => 'nowo_anonymize.faker.name_fallback',
                FakerType::HTML->value          => 'nowo_anonymize.faker.html',
                FakerType::PATTERN_BASED->value => 'nowo_anonymize.faker.pattern_based',
                FakerType::COPY->value          => 'nowo_anonymize.faker.copy',
                FakerType::NULL->value          => 'nowo_anonymize.faker.null',
                FakerType::UTM->value           => 'nowo_anonymize.faker.utm',
                FakerType::MAP->value           => 'nowo_anonymize.faker.map',
                FakerType::SERVICE->value       => null,
                default                         => null,
            };

            if ($serviceId !== null && $this->container->has($serviceId)) {
                return $this->container->get($serviceId);
            }
        }

        // Fallback to direct instantiation
        return match ($type) {
            FakerType::EMAIL->value         => new EmailFaker($this->locale),
            FakerType::NAME->value          => new NameFaker($this->locale),
            FakerType::SURNAME->value       => new SurnameFaker($this->locale),
            FakerType::AGE->value           => new AgeFaker($this->locale),
            FakerType::PHONE->value         => new PhoneFaker($this->locale),
            FakerType::IBAN->value          => new IbanFaker($this->locale),
            FakerType::CREDIT_CARD->value   => new CreditCardFaker($this->locale),
            FakerType::ADDRESS->value       => new AddressFaker($this->locale),
            FakerType::DATE->value          => new DateFaker($this->locale),
            FakerType::USERNAME->value      => new UsernameFaker($this->locale),
            FakerType::URL->value           => new UrlFaker($this->locale),
            FakerType::COMPANY->value       => new CompanyFaker($this->locale),
            FakerType::MASKING->value       => new MaskingFaker(),
            FakerType::PASSWORD->value      => new PasswordFaker($this->locale),
            FakerType::IP_ADDRESS->value    => new IpAddressFaker($this->locale),
            FakerType::MAC_ADDRESS->value   => new MacAddressFaker($this->locale),
            FakerType::UUID->value          => new UuidFaker($this->locale),
            FakerType::HASH->value          => new HashFaker($this->locale),
            FakerType::COORDINATE->value    => new CoordinateFaker($this->locale),
            FakerType::COLOR->value         => new ColorFaker($this->locale),
            FakerType::BOOLEAN->value       => new BooleanFaker($this->locale),
            FakerType::NUMERIC->value       => new NumericFaker($this->locale),
            FakerType::FILE->value          => new FileFaker($this->locale),
            FakerType::JSON->value          => new JsonFaker($this->locale),
            FakerType::TEXT->value          => new TextFaker($this->locale),
            FakerType::ENUM->value          => new EnumFaker($this->locale),
            FakerType::COUNTRY->value       => new CountryFaker($this->locale),
            FakerType::LANGUAGE->value      => new LanguageFaker($this->locale),
            FakerType::HASH_PRESERVE->value => new HashPreserveFaker(),
            FakerType::SHUFFLE->value       => new ShuffleFaker(),
            FakerType::CONSTANT->value      => new ConstantFaker(),
            FakerType::DNI_CIF->value       => new DniCifFaker($this->locale),
            FakerType::NAME_FALLBACK->value => new NameFallbackFaker($this->locale),
            FakerType::HTML->value          => new HtmlFaker($this->locale),
            FakerType::PATTERN_BASED->value => new PatternBasedFaker($this->locale),
            FakerType::COPY->value          => new CopyFaker(),
            FakerType::NULL->value          => new NullFaker(),
            FakerType::UTM->value           => new UtmFaker($this->locale),
            FakerType::MAP->value           => new MapFaker(),
            FakerType::SERVICE->value       => new ServiceFaker($this->container, $serviceName ?? ''),
            default                         => throw new InvalidArgumentException(sprintf('Unsupported faker type: %s', $type)),
        };
    }
}
