<?php

declare(strict_types=1);

namespace App\Application\Result;

/**
 * Collection of Results for batch operations.
 *
 * Useful when processing multiple items and collecting all results.
 *
 * @template T
 * @template E
 */
final class ResultCollection implements \Countable, \IteratorAggregate
{
    /**
     * @param array<Result<T, E>> $results
     */
    private function __construct(
        private array $results = [],
    ) {
    }

    /**
     * @return self<T, E>
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * @param array<Result<T, E>> $results
     *
     * @return self<T, E>
     */
    public static function fromArray(array $results): self
    {
        return new self($results);
    }

    /**
     * @param Result<T, E> $result
     *
     * @return self<T, E>
     */
    public function add(Result $result): self
    {
        $results = $this->results;
        $results[] = $result;

        return new self($results);
    }

    /**
     * Check if all results are successful.
     */
    public function allSuccessful(): bool
    {
        foreach ($this->results as $result) {
            if ($result->isFailure()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if any result is successful.
     */
    public function anySuccessful(): bool
    {
        foreach ($this->results as $result) {
            if ($result->isSuccess()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all successful values.
     *
     * @return array<T>
     */
    public function getSuccesses(): array
    {
        $values = [];

        foreach ($this->results as $result) {
            if ($result->isSuccess()) {
                $values[] = $result->getValue();
            }
        }

        return $values;
    }

    /**
     * Get all errors.
     *
     * @return array<E>
     */
    public function getErrors(): array
    {
        $errors = [];

        foreach ($this->results as $result) {
            if ($result->isFailure()) {
                $errors[] = $result->getError();
            }
        }

        return $errors;
    }

    /**
     * Combine all results into a single result.
     *
     * Returns success with all values if all successful,
     * or failure with all errors if any failed.
     *
     * @return Result<array<T>, array<E>>
     */
    public function combine(): Result
    {
        if ($this->allSuccessful()) {
            return Result::success($this->getSuccesses());
        }

        return Result::failure($this->getErrors());
    }

    public function count(): int
    {
        return \count($this->results);
    }

    /**
     * @return \Traversable<Result<T, E>>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->results);
    }
}
