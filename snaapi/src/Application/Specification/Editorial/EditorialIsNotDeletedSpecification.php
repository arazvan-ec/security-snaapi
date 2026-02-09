<?php

declare(strict_types=1);

namespace App\Application\Specification\Editorial;

use App\Application\Specification\AbstractSpecification;
use Ec\Editorial\Domain\Model\NewsBase;

/**
 * Specification: Editorial must not be deleted.
 *
 * @extends AbstractSpecification<NewsBase>
 */
final class EditorialIsNotDeletedSpecification extends AbstractSpecification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof NewsBase) {
            return false;
        }

        return !$candidate->isDeleted();
    }
}
