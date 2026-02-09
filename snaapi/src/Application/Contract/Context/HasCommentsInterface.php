<?php

declare(strict_types=1);

namespace App\Application\Contract\Context;

/**
 * ISP: Interface for contexts that provide comments data.
 *
 * Segregated interface following Interface Segregation Principle.
 */
interface HasCommentsInterface
{
    public function commentsCount(): int;

    public function hasComments(): bool;
}
