<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Value Object for Word Count.
 *
 * Encapsulates word count with validation and comparison capabilities.
 */
final readonly class WordCount implements \JsonSerializable
{
    private function __construct(
        private int $value,
    ) {
    }

    public static function fromInt(int $count): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Word count cannot be negative');
        }

        return new self($count);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return 0 === $this->value;
    }

    public function isShortArticle(int $threshold = 300): bool
    {
        return $this->value < $threshold;
    }

    public function isLongArticle(int $threshold = 1000): bool
    {
        return $this->value >= $threshold;
    }

    public function add(self $other): self
    {
        return new self($this->value + $other->value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
