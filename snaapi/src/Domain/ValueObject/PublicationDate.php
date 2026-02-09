<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Value Object for Publication Date.
 *
 * Immutable date representation with formatting capabilities.
 * Follows SRP: Only responsible for date representation and formatting.
 */
final readonly class PublicationDate implements \Stringable, \JsonSerializable
{
    private const DEFAULT_FORMAT = 'Y-m-d H:i:s';

    private function __construct(
        private \DateTimeImmutable $value,
    ) {
    }

    public static function fromDateTime(\DateTimeInterface $dateTime): self
    {
        $immutable = $dateTime instanceof \DateTimeImmutable
            ? $dateTime
            : \DateTimeImmutable::createFromInterface($dateTime);

        return new self($immutable);
    }

    public static function fromString(string $dateString, string $format = self::DEFAULT_FORMAT): self
    {
        $date = \DateTimeImmutable::createFromFormat($format, $dateString);

        if (false === $date) {
            throw new \InvalidArgumentException(sprintf('Invalid date format: %s', $dateString));
        }

        return new self($date);
    }

    public static function now(): self
    {
        return new self(new \DateTimeImmutable());
    }

    public function value(): \DateTimeImmutable
    {
        return $this->value;
    }

    public function format(string $format = self::DEFAULT_FORMAT): string
    {
        return $this->value->format($format);
    }

    public function toIso8601(): string
    {
        return $this->value->format(\DateTimeInterface::ATOM);
    }

    public function isBefore(self $other): bool
    {
        return $this->value < $other->value;
    }

    public function isAfter(self $other): bool
    {
        return $this->value > $other->value;
    }

    public function equals(self $other): bool
    {
        return $this->value->getTimestamp() === $other->value->getTimestamp();
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public function jsonSerialize(): string
    {
        return $this->format();
    }
}
