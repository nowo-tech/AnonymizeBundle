<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Ddd\Domain;

/**
 * Plain-PHP domain entity of a strict DDD host: no bundle attribute, no trait, no Doctrine attribute (mapped in
 * config/doctrine/Patient.orm.xml), UUID string id, an embedded value object and an `anonymized` column of its own.
 */
class Patient
{
    private bool $anonymized = false;

    public function __construct(
        private readonly string $id,
        private readonly string $email,
        private readonly string $fullName,
        private readonly string $status,
        private readonly ContactDetails $contact,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function contact(): ContactDetails
    {
        return $this->contact;
    }

    public function isAnonymized(): bool
    {
        return $this->anonymized;
    }
}
