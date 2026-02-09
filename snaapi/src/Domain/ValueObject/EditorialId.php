<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Value Object for Editorial ID.
 *
 * Immutable, self-validating, and encapsulates the identity concept.
 * Follows SRP: Only responsible for representing an editorial identifier.
 */
final readonly class EditorialId implements \Stringable
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        if ('' === trim($value)) {
            throw new \InvalidArgumentException('Editorial ID cannot be empty');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
