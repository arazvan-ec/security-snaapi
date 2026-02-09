<?php

declare(strict_types=1);

namespace App\Application\Contract\Context;

use Ec\Section\Domain\Model\Section;

/**
 * ISP: Interface for contexts that provide section data.
 *
 * Segregated interface following Interface Segregation Principle.
 */
interface HasSectionInterface
{
    public function section(): ?Section;

    public function hasSection(): bool;
}
