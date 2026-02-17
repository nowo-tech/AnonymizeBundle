<?php

declare(strict_types=1);

namespace Nowo\AnonymizeBundle\Trait;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait for entities that support anonymization tracking.
 *
 * This trait adds an `anonymized` boolean field to track whether a record
 * has been anonymized. Use this trait in entities that you want to track
 * anonymization status.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
trait AnonymizableTrait
{
    /**
     * Indicates whether this record has been anonymized.
     */
    #[ORM\Column(name: 'anonymized', type: 'boolean', nullable: false, options: ['default' => false])]
    private bool $anonymized = false;

    /**
     * Gets whether this record has been anonymized.
     *
     * @return bool True if the record has been anonymized, false otherwise
     */
    public function isAnonymized(): bool
    {
        return $this->anonymized;
    }

    /**
     * Sets whether this record has been anonymized.
     *
     * @param bool $anonymized True if the record has been anonymized, false otherwise
     *
     * @return $this
     */
    public function setAnonymized(bool $anonymized): self
    {
        $this->anonymized = $anonymized;

        return $this;
    }
}
