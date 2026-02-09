<?php

declare(strict_types=1);

namespace App\Application\Contract\Context;

/**
 * ISP: Interface for contexts that provide membership data.
 *
 * Segregated interface following Interface Segregation Principle.
 */
interface HasMembershipInterface
{
    /**
     * @return array<string, string>
     */
    public function membershipLinks(): array;

    public function hasMembershipLinks(): bool;
}
