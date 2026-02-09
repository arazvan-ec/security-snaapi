<?php

declare(strict_types=1);

namespace App\Application\Result;

/**
 * Value Object representing an error in the Result pattern.
 *
 * Provides structured error information for consistent error handling.
 */
final readonly class Error implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private string $code,
        private string $message,
        private array $context = [],
        private ?\Throwable $previous = null,
    ) {
    }

    public static function notFound(string $resource, string $id): self
    {
        return new self(
            'NOT_FOUND',
            sprintf('%s not found: %s', $resource, $id),
            ['resource' => $resource, 'id' => $id]
        );
    }

    public static function validationFailed(string $message, array $violations = []): self
    {
        return new self(
            'VALIDATION_FAILED',
            $message,
            ['violations' => $violations]
        );
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self('UNAUTHORIZED', $message);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self('FORBIDDEN', $message);
    }

    public static function internal(string $message, ?\Throwable $previous = null): self
    {
        return new self('INTERNAL_ERROR', $message, [], $previous);
    }

    public static function gateway(string $service, string $message, ?\Throwable $previous = null): self
    {
        return new self(
            'GATEWAY_ERROR',
            sprintf('Gateway error from %s: %s', $service, $message),
            ['service' => $service],
            $previous
        );
    }

    public function code(): string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    public function previous(): ?\Throwable
    {
        return $this->previous;
    }

    public function withContext(string $key, mixed $value): self
    {
        return new self(
            $this->code,
            $this->message,
            array_merge($this->context, [$key => $value]),
            $this->previous
        );
    }

    /**
     * @return array{code: string, message: string, context: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
