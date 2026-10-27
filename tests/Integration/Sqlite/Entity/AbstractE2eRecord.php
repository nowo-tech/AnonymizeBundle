<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Tests\Integration\Sqlite\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nowo\AnonymizeBundle\Trait\AnonymizableTrait;

/**
 * Mapped superclass carrying AnonymizableTrait (trait detected on a parent class).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
#[ORM\MappedSuperclass]
abstract class AbstractE2eRecord
{
    use AnonymizableTrait;
}
