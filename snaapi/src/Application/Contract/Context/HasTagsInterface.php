<?php

declare(strict_types=1);

namespace App\Application\Contract\Context;

use Ec\Tag\Domain\Model\Tag;

/**
 * ISP: Interface for contexts that provide tags data.
 *
 * Segregated interface following Interface Segregation Principle.
 */
interface HasTagsInterface
{
    /**
     * @return Tag[]
     */
    public function tags(): array;

    public function hasTags(): bool;
}
