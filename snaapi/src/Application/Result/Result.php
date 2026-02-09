<?php

declare(strict_types=1);

namespace App\Application\Result;

/**
 * Result Pattern (Either Monad): Represents either success or failure.
 *
 * This pattern replaces inconsistent error handling (exceptions vs null returns)
 * with a unified, type-safe approach.
 *
 * Benefits:
 * - Explicit error handling at compile time
 * - No hidden exceptions
 * - Composable operations
 * - Self-documenting API
 *
 * @template T
 * @template E
 */
final readonly class Result
{
    /**
     * @param T|null $value
     * @param E|null $error
     */
    private function __construct(
        private mixed $value,
        private mixed $error,
        private bool $isSuccess,
    ) {
    }

    /**
     * Create a success result.
     *
     * @template V
     *
     * @param V $value
     *
     * @return Result<V, never>
     */
    public static function success(mixed $value): self
    {
        return new self($value, null, true);
    }

    /**
     * Create a failure result.
     *
     * @template F
     *
     * @param F $error
     *
     * @return Result<never, F>
     */
    public static function failure(mixed $error): self
    {
        return new self(null, $error, false);
    }

    /**
     * Create a result from a nullable value.
     *
     * @template V
     * @template F
     *
     * @param V|null $value
     * @param F      $errorIfNull
     *
     * @return Result<V, F>
     */
    public static function fromNullable(mixed $value, mixed $errorIfNull): self
    {
        if (null === $value) {
            return self::failure($errorIfNull);
        }

        return self::success($value);
    }

    /**
     * Check if this is a success result.
     */
    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    /**
     * Check if this is a failure result.
     */
    public function isFailure(): bool
    {
        return !$this->isSuccess;
    }

    /**
     * Get the success value or throw if failure.
     *
     * @return T
     *
     * @throws \RuntimeException
     */
    public function getValue(): mixed
    {
        if (!$this->isSuccess) {
            throw new \RuntimeException('Cannot get value from failure result');
        }

        return $this->value;
    }

    /**
     * Get the error or throw if success.
     *
     * @return E
     *
     * @throws \RuntimeException
     */
    public function getError(): mixed
    {
        if ($this->isSuccess) {
            throw new \RuntimeException('Cannot get error from success result');
        }

        return $this->error;
    }

    /**
     * Get value or return default if failure.
     *
     * @template D
     *
     * @param D $default
     *
     * @return T|D
     */
    public function getValueOr(mixed $default): mixed
    {
        return $this->isSuccess ? $this->value : $default;
    }

    /**
     * Map the success value.
     *
     * @template U
     *
     * @param callable(T): U $mapper
     *
     * @return Result<U, E>
     */
    public function map(callable $mapper): self
    {
        if (!$this->isSuccess) {
            return $this;
        }

        return self::success($mapper($this->value));
    }

    /**
     * Map the error value.
     *
     * @template F
     *
     * @param callable(E): F $mapper
     *
     * @return Result<T, F>
     */
    public function mapError(callable $mapper): self
    {
        if ($this->isSuccess) {
            return $this;
        }

        return self::failure($mapper($this->error));
    }

    /**
     * FlatMap (bind) for chaining operations.
     *
     * @template U
     *
     * @param callable(T): Result<U, E> $mapper
     *
     * @return Result<U, E>
     */
    public function flatMap(callable $mapper): self
    {
        if (!$this->isSuccess) {
            return $this;
        }

        return $mapper($this->value);
    }

    /**
     * Execute side effect on success.
     *
     * @param callable(T): void $callback
     *
     * @return self<T, E>
     */
    public function onSuccess(callable $callback): self
    {
        if ($this->isSuccess) {
            $callback($this->value);
        }

        return $this;
    }

    /**
     * Execute side effect on failure.
     *
     * @param callable(E): void $callback
     *
     * @return self<T, E>
     */
    public function onFailure(callable $callback): self
    {
        if (!$this->isSuccess) {
            $callback($this->error);
        }

        return $this;
    }

    /**
     * Fold the result into a single value.
     *
     * @template R
     *
     * @param callable(T): R $onSuccess
     * @param callable(E): R $onFailure
     *
     * @return R
     */
    public function fold(callable $onSuccess, callable $onFailure): mixed
    {
        return $this->isSuccess
            ? $onSuccess($this->value)
            : $onFailure($this->error);
    }
}
