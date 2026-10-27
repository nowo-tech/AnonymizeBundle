<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Related entity used by relationship patterns (customer.company.name).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'e2e_company')]
class E2eCompany
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    public function __construct(
        #[ORM\Column(name: 'company_name', type: 'string', length: 100)]
        public string $name,
        #[ORM\Column(type: 'string', length: 2)]
        public string $country = 'ES',
        #[ORM\Column(type: 'float')]
        public float $rating = 0.0,
    ) {
    }
}
