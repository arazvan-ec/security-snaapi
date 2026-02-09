<?php

declare(strict_types=1);

namespace App\Application\Factory\Response;

use App\Application\DTO\Response\TitlesResponse;
use Ec\Editorial\Domain\Model\Titles;

final readonly class TitlesResponseFactory
{
    public function create(Titles $titles): TitlesResponse
    {
        return new TitlesResponse(
            preTitle: $titles->preTitle(),
            title: $titles->title(),
            urlTitle: $titles->urlTitle(),
        );
    }
}
