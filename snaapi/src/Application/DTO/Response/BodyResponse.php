<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

/**
 * DTO for editorial body.
 */
final readonly class BodyResponse implements \JsonSerializable
{
    /**
     * @param BodyElementResponse[] $elements
     */
    public function __construct(
        public string $type,
        public array $elements,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'elements' => $this->elements,
        ];
    }
}
