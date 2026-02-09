<?php

declare(strict_types=1);

namespace App\Application\Specification\Editorial;

use App\Application\Specification\AbstractSpecification;
use Ec\Editorial\Domain\Model\NewsBase;

/**
 * Composite Specification: Editorial is available for public viewing.
 *
 * Combines: published AND not deleted.
 *
 * @extends AbstractSpecification<NewsBase>
 */
final class EditorialIsAvailableSpecification extends AbstractSpecification
{
    private readonly EditorialIsPublishedSpecification $isPublished;
    private readonly EditorialIsNotDeletedSpecification $isNotDeleted;

    public function __construct()
    {
        $this->isPublished = new EditorialIsPublishedSpecification();
        $this->isNotDeleted = new EditorialIsNotDeletedSpecification();
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $this->isPublished->and($this->isNotDeleted)->isSatisfiedBy($candidate);
    }
}
