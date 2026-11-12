<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Equivalence\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Composite primary key (tenant, number): the run pages with LIMIT / OFFSET. No bundle attribute (selected by
 * configuration); an "anonymized" column without AnonymizableTrait.
 */
#[ORM\Entity]
#[ORM\Table(name: 'eq_ticket')]
class EqTicket
{
    #[ORM\Column(type: 'boolean')]
    public bool $anonymized = false;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 10)]
        public string $tenant,
        #[ORM\Id]
        #[ORM\Column(type: 'integer')]
        public int $number,
        #[ORM\Column(type: 'string', length: 100)]
        public string $subject,
        #[ORM\Column(type: 'string', length: 100)]
        public string $requester,
        #[ORM\Column(type: 'string', length: 20)]
        public string $status,
    ) {
    }
}
