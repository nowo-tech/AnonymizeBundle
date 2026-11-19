<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Concurrency\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

/**
 * Single-column key: keyset chunks on "id".
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'cc_customer')]
class CcCustomer
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'integer')]
        public int $id,
        #[ORM\Column(type: 'string', length: 180)]
        #[AnonymizeProperty(type: 'email')]
        public string $email,
        #[ORM\ManyToOne(targetEntity: CcAccount::class)]
        #[ORM\JoinColumn(name: 'account_id', referencedColumnName: 'id', nullable: true)]
        public ?CcAccount $account = null,
    ) {
    }
}
