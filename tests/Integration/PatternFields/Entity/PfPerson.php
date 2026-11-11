<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\PatternFields\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * JOINED inheritance child: a run reads only its own table (audit LQ-N-D-I1).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'pf_person')]
class PfPerson extends PfParty
{
    #[ORM\Column(type: 'string', length: 100)]
    public string $nickname = '';

    #[ORM\ManyToOne(targetEntity: PfCustomer::class)]
    #[ORM\JoinColumn(name: 'employer_id', referencedColumnName: 'id')]
    public ?PfCustomer $employer = null;
}
