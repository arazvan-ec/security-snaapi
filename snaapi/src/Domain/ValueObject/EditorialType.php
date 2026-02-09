<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

/**
 * Value Object for Editorial Type.
 *
 * Encapsulates editorial type with its ID and human-readable name.
 * Follows OCP: New types can be added without modifying existing code.
 */
final readonly class EditorialType implements \JsonSerializable
{
    private const TYPE_NAMES = [
        1 => 'news',
        2 => 'blog',
        3 => 'opinion',
        4 => 'analysis',
        5 => 'interview',
        6 => 'report',
        7 => 'feature',
        8 => 'review',
    ];

    private function __construct(
        private int $id,
        private string $name,
    ) {
    }

    public static function fromId(int $id): self
    {
        $name = self::TYPE_NAMES[$id] ?? 'unknown';

        return new self($id, $name);
    }

    public static function news(): self
    {
        return self::fromId(1);
    }

    public static function blog(): self
    {
        return self::fromId(2);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isNews(): bool
    {
        return 1 === $this->id;
    }

    public function isBlog(): bool
    {
        return 2 === $this->id;
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }

    /**
     * @return array{id: string, name: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
        ];
    }
}
