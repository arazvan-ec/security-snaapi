<?php

declare(strict_types=1);

namespace App\Application\Specification\Editorial;

use App\Application\Specification\AbstractSpecification;
use Ec\Editorial\Domain\Model\NewsBase;

/**
 * Specification: Editorial allows comments.
 *
 * @extends AbstractSpecification<NewsBase>
 */
final class EditorialIsCommentableSpecification extends AbstractSpecification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (!$candidate instanceof NewsBase) {
            return false;
        }

        return $candidate->isCommentable();
    }
}
