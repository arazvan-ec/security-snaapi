<?php

declare(strict_types=1);

namespace App\Application\Contract\Context;

use Ec\Editorial\Domain\Model\NewsBase;

/**
 * ISP: Interface for contexts that provide editorial data.
 *
 * Segregated interface following Interface Segregation Principle.
 * Clients that only need editorial data depend only on this interface.
 */
interface HasEditorialInterface
{
    public function editorial(): ?NewsBase;

    public function hasEditorial(): bool;
}
