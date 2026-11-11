<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Ddd\Domain;

/** Value object embedded in Patient (XML `<embedded>`), without any attribute. */
final readonly class ContactDetails
{
    public function __construct(private ?string $phone, private ?string $city)
    {
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function city(): ?string
    {
        return $this->city;
    }
}
