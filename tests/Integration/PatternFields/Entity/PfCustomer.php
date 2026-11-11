<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\PatternFields\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

/**
 * Doctrine's default naming strategy keeps the camelCase column name "accountStatus" unquoted, so PostgreSQL stores
 * (and returns) it as "accountstatus" (audit LQ-N-D-L1).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'pf_customer')]
class PfCustomer
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'integer')]
        public int $id,
        #[ORM\Column(type: 'string', length: 180)]
        #[AnonymizeProperty(type: 'email')]
        public string $email,
        #[ORM\Column(type: 'string', length: 20)]
        public string $accountStatus,
    ) {
    }
}
