<?php

declare(strict_types=1);

namespace App\Application\Factory\Response;

use App\Application\DTO\Response\SectionResponse;
use Ec\Section\Domain\Model\Section;

final readonly class SectionResponseFactory
{
    public function create(Section $section): SectionResponse
    {
        return new SectionResponse(
            id: $section->id(),
            name: $section->name(),
            url: $section->url(),
            siteId: $section->siteId(),
        );
    }
}
