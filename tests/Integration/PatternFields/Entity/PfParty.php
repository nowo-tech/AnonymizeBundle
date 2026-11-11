<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\PatternFields\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * JOINED inheritance root: its table holds the discriminator, the inherited field and the inherited association.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'pf_party')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string')]
#[ORM\DiscriminatorMap(['party' => PfParty::class, 'person' => PfPerson::class])]
class PfParty
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    public int $id = 1;

    #[ORM\Column(type: 'string', length: 100)]
    public string $display_name = '';

    #[ORM\ManyToOne(targetEntity: PfCustomer::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    public ?PfCustomer $owner = null;
}
