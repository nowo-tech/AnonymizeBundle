<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

/**
 * Embeddable with an anonymized field (Doctrine path address.street).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[ORM\Embeddable]
class E2eAddress
{
    public function __construct(
        #[AnonymizeProperty(type: 'constant', weight: 9, options: ['value' => 'Anonymized street 1'])]
        #[ORM\Column(type: 'string', length: 100, nullable: true)]
        public ?string $street = null,
    ) {
    }
}
