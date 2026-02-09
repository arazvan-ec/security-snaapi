<?php

declare(strict_types=1);

namespace App\Application\Specification;

/**
 * Specification Pattern: Interface for business rule specifications.
 *
 * OCP: New specifications can be added without modifying existing code.
 * SRP: Each specification encapsulates a single business rule.
 *
 * @template T
 */
interface SpecificationInterface
{
    /**
     * Check if the candidate satisfies this specification.
     *
     * @param T $candidate
     */
    public function isSatisfiedBy(mixed $candidate): bool;

    /**
     * Create a composite specification that requires both this and another.
     *
     * @param SpecificationInterface<T> $other
     *
     * @return SpecificationInterface<T>
     */
    public function and(self $other): self;

    /**
     * Create a composite specification that requires either this or another.
     *
     * @param SpecificationInterface<T> $other
     *
     * @return SpecificationInterface<T>
     */
    public function or(self $other): self;

    /**
     * Create a specification that is the negation of this one.
     *
     * @return SpecificationInterface<T>
     */
    public function not(): self;
}
