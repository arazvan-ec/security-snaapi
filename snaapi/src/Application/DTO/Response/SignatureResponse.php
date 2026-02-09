<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

/**
 * DTO for journalist signature.
 */
final readonly class SignatureResponse implements \JsonSerializable
{
    public function __construct(
        public string $aliasId,
        public string $name,
        public ?string $description,
        public ?string $url,
        public ?string $photoUrl,
        public ?string $twitter,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'aliasId' => $this->aliasId,
            'name' => $this->name,
            'description' => $this->description,
            'url' => $this->url,
            'photoUrl' => $this->photoUrl,
            'twitter' => $this->twitter,
        ], fn ($v) => null !== $v);
    }
}
