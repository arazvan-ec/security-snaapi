<?php

declare(strict_types=1);

namespace App\Application\Factory\Response;

use App\Application\DTO\Response\SignatureResponse;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Section\Domain\Model\Section;

final readonly class SignatureResponseFactory
{
    public function __construct(
        private string $extension = '',
    ) {
    }

    public function create(
        string $aliasId,
        Journalist $journalist,
        Section $section,
        bool $hasTwitter = false
    ): SignatureResponse {
        $url = $this->buildJournalistUrl($journalist, $section);

        return new SignatureResponse(
            aliasId: $aliasId,
            name: $journalist->name(),
            description: $journalist->description(),
            url: $url,
            photoUrl: $journalist->photoUrl(),
            twitter: $hasTwitter ? $journalist->twitter() : null,
        );
    }

    private function buildJournalistUrl(Journalist $journalist, Section $section): ?string
    {
        $slug = $journalist->slug();

        if (null === $slug || '' === $slug) {
            return null;
        }

        return sprintf(
            '%s/autores/%s%s',
            rtrim($section->url(), '/'),
            $slug,
            $this->extension
        );
    }
}
