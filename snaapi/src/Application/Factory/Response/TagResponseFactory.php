<?php

declare(strict_types=1);

namespace App\Application\Factory\Response;

use App\Application\DTO\Response\TagResponse;
use Ec\Tag\Domain\Model\Tag;

final readonly class TagResponseFactory
{
    public function create(Tag $tag): TagResponse
    {
        return new TagResponse(
            id: $tag->id(),
            name: $tag->name(),
            url: $tag->url(),
        );
    }
}
