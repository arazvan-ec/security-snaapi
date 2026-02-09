<?php

declare(strict_types=1);

namespace App\Application\Specification;

/**
 * Specification that negates another specification.
 *
 * @template T
 *
 * @extends AbstractSpecification<T>
 */
final class NotSpecification extends AbstractSpecification
{
    /**
     * @param SpecificationInterface<T> $specification
     */
    public function __construct(
        private readonly SpecificationInterface $specification,
    ) {
    }

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return !$this->specification->isSatisfiedBy($candidate);
    }
}
