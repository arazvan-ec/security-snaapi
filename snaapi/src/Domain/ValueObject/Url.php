<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Value Object for URL.
 *
 * Self-validating URL representation.
 * Follows SRP: Only responsible for URL validation and representation.
 */
final readonly class Url implements \Stringable, \JsonSerializable
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $url): self
    {
        if ('' === trim($url)) {
            throw new \InvalidArgumentException('URL cannot be empty');
        }

        if (false === filter_var($url, FILTER_VALIDATE_URL)) {
            // Allow relative URLs
            if (!str_starts_with($url, '/')) {
                throw new \InvalidArgumentException(sprintf('Invalid URL: %s', $url));
            }
        }

        return new self($url);
    }

    public static function relative(string $path): self
    {
        $path = '/' . ltrim($path, '/');

        return new self($path);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isAbsolute(): bool
    {
        return str_starts_with($this->value, 'http://') || str_starts_with($this->value, 'https://');
    }

    public function isRelative(): bool
    {
        return !$this->isAbsolute();
    }

    public function withPath(string $path): self
    {
        if ($this->isRelative()) {
            return self::relative($path);
        }

        $parsed = parse_url($this->value);

        return new self(sprintf(
            '%s://%s%s',
            $parsed['scheme'] ?? 'https',
            $parsed['host'] ?? '',
            '/' . ltrim($path, '/')
        ));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
