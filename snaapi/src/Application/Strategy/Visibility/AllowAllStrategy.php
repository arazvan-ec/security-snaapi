<?php

declare(strict_types=1);

namespace App\Application\Strategy\Visibility;

use Ec\Editorial\Domain\Model\NewsBase;

final class AllowAllStrategy implements VisibilityStrategyInterface
{
    public function checkVisibility(NewsBase $editorial): void
    {
        // No visibility check — all editorials are accessible
    }
}
