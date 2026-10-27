<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Attribute\Anonymize;
use Nowo\AnonymizeBundle\Attribute\AnonymizeProperty;

/**
 * Entity pseudonymised with hash_preserve (needs a usable HMAC key, checked before any write).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[ORM\Entity]
#[ORM\Table(name: 'e2e_api_token')]
#[Anonymize]
class E2eApiToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public ?int $id = null;

    public function __construct(
        #[AnonymizeProperty(type: 'hash_preserve')]
        #[ORM\Column(type: 'string', length: 128)]
        public string $token,
    ) {
    }
}
