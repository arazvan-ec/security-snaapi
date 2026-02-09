<?php

declare(strict_types=1);

namespace App\Application\Strategy\Visibility;

use App\Exception\EditorialNotPublishedYetException;
use Ec\Editorial\Domain\Model\NewsBase;

final class PublishedOnlyStrategy implements VisibilityStrategyInterface
{
    public function checkVisibility(NewsBase $editorial): void
    {
        if (!$editorial->isVisible()) {
            throw new EditorialNotPublishedYetException();
        }
    }
}
