<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Concurrency\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

/**
 * VARCHAR key with digit-only values (audit UM-AN-1): keys must compare as strings across chunk boundaries.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'cc_code')]
class CcCode
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 20)]
        public string $code,
        #[ORM\Column(type: 'string', length: 180)]
        #[AnonymizeProperty(type: 'email')]
        public string $email,
    ) {
    }
}
