<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Concurrency\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Joined by the customers' relationship pattern ("account.status"), so the chunk read carries a LEFT JOIN.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'cc_account')]
class CcAccount
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'integer')]
        public int $id,
        #[ORM\Column(type: 'string', length: 20)]
        public string $status,
    ) {
    }
}
