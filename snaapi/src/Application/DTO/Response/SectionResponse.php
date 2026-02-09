<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

/**
 * DTO for section data.
 */
final readonly class SectionResponse implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public string $url,
        public ?string $siteId = null,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'siteId' => $this->siteId,
        ], fn ($v) => null !== $v);
    }
}
