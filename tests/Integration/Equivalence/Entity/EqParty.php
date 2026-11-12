<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Equivalence\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Single-table inheritance root: only the selected child's rows (discriminator) may be read and written.
 */
#[ORM\Entity]
#[ORM\Table(name: 'eq_party')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string')]
#[ORM\DiscriminatorMap(['person' => EqPerson::class, 'company' => EqCompany::class])]
abstract class EqParty
{
    #[ORM\Column(type: 'boolean')]
    public bool $anonymized = false;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'integer')]
        public int $id,
        #[ORM\Column(type: 'string', length: 100)]
        public string $name,
        #[ORM\Column(type: 'string', length: 100)]
        public string $email,
        #[ORM\Column(type: 'string', length: 20)]
        public string $status,
    ) {
    }
}
