<?php

declare(strict_types=1);

namespace App\Application\Specification;

/**
 * Abstract base for specifications with composite operations.
 *
 * Provides default implementations for composite specification methods.
 *
 * @template T
 *
 * @implements SpecificationInterface<T>
 */
abstract class AbstractSpecification implements SpecificationInterface
{
    abstract public function isSatisfiedBy(mixed $candidate): bool;

    /**
     * @param SpecificationInterface<T> $other
     *
     * @return SpecificationInterface<T>
     */
    public function and(SpecificationInterface $other): SpecificationInterface
    {
        return new AndSpecification($this, $other);
    }

    /**
     * @param SpecificationInterface<T> $other
     *
     * @return SpecificationInterface<T>
     */
    public function or(SpecificationInterface $other): SpecificationInterface
    {
        return new OrSpecification($this, $other);
    }

    /**
     * @return SpecificationInterface<T>
     */
    public function not(): SpecificationInterface
    {
        return new NotSpecification($this);
    }
}
