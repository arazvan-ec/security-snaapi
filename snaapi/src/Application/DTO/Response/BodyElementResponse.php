<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

/**
 * DTO for a body element.
 *
 * Uses optional properties to support different element types.
 */
final readonly class BodyElementResponse implements \JsonSerializable
{
    /**
     * @param array<string, mixed>|null $links
     * @param array<string, mixed>|null $items
     * @param array<string, mixed>|null $extra
     */
    public function __construct(
        public string $type,
        public ?string $content = null,
        public ?int $level = null,
        public ?string $imageUrl = null,
        public ?string $caption = null,
        public ?string $credit = null,
        public ?string $videoId = null,
        public ?string $videoUrl = null,
        public ?string $html = null,
        public ?array $links = null,
        public ?array $items = null,
        public ?array $extra = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'type' => $this->type,
            'content' => $this->content,
            'level' => $this->level,
            'imageUrl' => $this->imageUrl,
            'caption' => $this->caption,
            'credit' => $this->credit,
            'videoId' => $this->videoId,
            'videoUrl' => $this->videoUrl,
            'html' => $this->html,
            'links' => $this->links,
            'items' => $this->items,
            'extra' => $this->extra,
        ], fn ($v) => null !== $v);
    }
}
