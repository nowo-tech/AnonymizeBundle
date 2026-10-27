<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

/**
 * Customer anonymized property by property; only customers of "Acme%" companies are processed.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'e2e_customer')]
#[Anonymize(includePatterns: ['company.name' => 'Acme%'])]
class E2eCustomer extends AbstractE2eRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    #[ORM\Embedded(class: E2eAddress::class)]
    public E2eAddress $address;

    public function __construct(
        #[AnonymizeProperty(type: 'email', weight: 1, excludePatterns: ['email' => '%@keep.test'])]
        #[ORM\Column(type: 'string', length: 180)]
        public string $email,
        #[AnonymizeProperty(type: 'masking', weight: 2, options: ['preserve_start' => 1])]
        #[ORM\Column(type: 'string', length: 100)]
        public string $name,
        #[AnonymizeProperty(type: 'name_fallback', weight: 3, options: ['fallback_field' => 'name'])]
        #[ORM\Column(name: 'nick_name', type: 'string', length: 100, nullable: true)]
        public ?string $nickName,
        #[AnonymizeProperty(type: 'age', weight: 4, options: ['min' => 30, 'max' => 40])]
        #[ORM\Column(name: 'customer_age', type: 'integer')]
        public int $age,
        #[AnonymizeProperty(type: 'boolean', weight: 5, includePatterns: ['customer_age' => '>=18'])]
        #[ORM\Column(type: 'boolean')]
        public bool $vip,
        #[ORM\ManyToOne(targetEntity: E2eCompany::class)]
        #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: true)]
        public ?E2eCompany $company = null,
        #[AnonymizeProperty(type: 'text', weight: 6, options: ['preserve_null' => true])]
        #[ORM\Column(type: 'string', length: 255, nullable: true)]
        public ?string $notes = null,
    ) {
        $this->address = new E2eAddress('Real street 42');
    }
}
