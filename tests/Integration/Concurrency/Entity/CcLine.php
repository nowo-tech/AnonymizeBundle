<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Concurrency\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

/**
 * Composite key (order_id, line_no): keyset chunks compare both columns.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'cc_line')]
class CcLine
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(name: 'order_id', type: 'integer')]
        public int $orderId,
        #[ORM\Id]
        #[ORM\Column(name: 'line_no', type: 'integer')]
        public int $lineNo,
        #[ORM\Column(type: 'string', length: 180)]
        #[AnonymizeProperty(type: 'email')]
        public string $email,
    ) {
    }
}
