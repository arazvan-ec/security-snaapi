<?php

declare(strict_types=1);

namespace App\Application\Specification;

/**
 * Composite specification that requires both specifications to be satisfied.
 *
 * @template T
 *
 * @extends AbstractSpecification<T>
 */
final class AndSpecification extends AbstractSpecification
{
    /**
     * @param SpecificationInterface<T> $left
     * @param SpecificationInterface<T> $right
     */
    public function __construct(
        private readonly SpecificationInterface $left,
        private readonly SpecificationInterface $right,
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $this->left->isSatisfiedBy($candidate) && $this->right->isSatisfiedBy($candidate);
    }
}
