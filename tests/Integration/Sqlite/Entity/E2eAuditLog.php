<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;

/**
 * Entity anonymized by a custom service (no #[AnonymizeProperty]).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'e2e_audit_log')]
#[Anonymize(connection: 'default', anonymizeService: 'e2e.audit_log_anonymizer')]
class E2eAuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    public function __construct(
        #[ORM\Column(type: 'string', length: 255)]
        public string $message,
    ) {
    }
}
