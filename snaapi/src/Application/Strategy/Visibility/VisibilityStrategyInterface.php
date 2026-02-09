<?php

declare(strict_types=1);

namespace App\Application\Strategy\Visibility;

use Ec\Editorial\Domain\Model\NewsBase;

interface VisibilityStrategyInterface
{
    /**
     * Check if the editorial is visible according to this strategy.
     *
     * @throws \App\Exception\EditorialNotPublishedYetException
     */
    public function checkVisibility(NewsBase $editorial): void;
}
