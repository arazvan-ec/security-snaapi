<?php

declare(strict_types=1);

namespace App\Application\Specification\Editorial;

use App\Application\Specification\AbstractSpecification;
use Ec\Editorial\Domain\Model\NewsBase;

/**
 * Specification: Editorial must be indexable by search engines.
 *
 * @extends AbstractSpecification<NewsBase>
 */
final class EditorialIsIndexableSpecification extends AbstractSpecification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof NewsBase) {
            return false;
        }

        return $candidate->isIndexable();
    }
}
