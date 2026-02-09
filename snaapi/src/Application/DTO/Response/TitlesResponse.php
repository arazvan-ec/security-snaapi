<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

/**
 * DTO for editorial titles.
 */
final readonly class TitlesResponse implements \JsonSerializable
{
    public function __construct(
        public ?string $preTitle,
        public string $title,
        public string $urlTitle,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function jsonSerialize(): array
    {
        return [
            'preTitle' => $this->preTitle,
            'title' => $this->title,
            'urlTitle' => $this->urlTitle,
        ];
    }
}
