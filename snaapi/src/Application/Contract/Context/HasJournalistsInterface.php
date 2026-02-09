<?php

declare(strict_types=1);

namespace App\Application\Contract\Context;

use Ec\Journalist\Domain\Model\Journalist;

/**
 * ISP: Interface for contexts that provide journalists data.
 *
 * Segregated interface following Interface Segregation Principle.
 */
interface HasJournalistsInterface
{
    /**
     * @return array<string, Journalist>
     */
    public function journalists(): array;

    public function hasJournalists(): bool;

    public function journalist(string $aliasId): ?Journalist;
}
