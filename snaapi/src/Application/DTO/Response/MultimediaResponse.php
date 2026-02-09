<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

/**
 * DTO for multimedia data.
 */
final readonly class MultimediaResponse implements \JsonSerializable
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public string $type,
        public ?string $id = null,
        public ?string $url = null,
        public ?string $caption = null,
        public ?string $credit = null,
        public ?array $metadata = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'type' => $this->type,
            'id' => $this->id,
            'url' => $this->url,
            'caption' => $this->caption,
            'credit' => $this->credit,
            'metadata' => $this->metadata,
        ], fn ($v) => null !== $v);
    }
}
