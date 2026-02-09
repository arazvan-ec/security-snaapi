<?php

declare(strict_types=1);

namespace App\Application\Strategy\Editorial;

use Ec\Editorial\Domain\Model\EditorialBlog;
use Ec\Editorial\Domain\Model\NewsBase;

/**
 * Strategy: Field extractor for Blog editorials.
 *
 * LSP: Properly handles EditorialBlog-specific fields without reflection.
 * This replaces method_exists() checks with proper type handling.
 */
final readonly class BlogEditorialFieldExtractor implements EditorialFieldExtractorInterface
{
    public function supports(NewsBase $editorial): bool
    {
        return $editorial instanceof EditorialBlog;
    }

    public function priority(): int
    {
        return 100;
    }

    public function extractEndOn(NewsBase $editorial): ?\DateTimeInterface
    {
        if (!$editorial instanceof EditorialBlog) {
            return null;
        }

        return $editorial->endOn();
    }

    public function extractClosingModeId(NewsBase $editorial): ?string
    {
        if (!$editorial instanceof EditorialBlog) {
            return null;
        }

        return $editorial->closingModeId();
    }

    public function extractIsBrand(NewsBase $editorial): bool
    {
        if (!$editorial instanceof EditorialBlog) {
            return false;
        }

        return $editorial->isBrand();
    }

    public function extractIsAmazonOnsite(NewsBase $editorial): bool
    {
        if (!$editorial instanceof EditorialBlog) {
            return false;
        }

        return $editorial->isAmazonOnsite();
    }

    public function extractContentType(NewsBase $editorial): ?string
    {
        if (!$editorial instanceof EditorialBlog) {
            return null;
        }

        return $editorial->contentType();
    }

    public function extractCanonicalEditorialId(NewsBase $editorial): ?string
    {
        if (!$editorial instanceof EditorialBlog) {
            return null;
        }

        return $editorial->canonicalEditorialId()?->id();
    }

    public function extractUrlDate(NewsBase $editorial): ?\DateTimeInterface
    {
        if (!$editorial instanceof EditorialBlog) {
            return null;
        }

        return $editorial->urlDate();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function extractStandfirst(NewsBase $editorial): ?array
    {
        if (!$editorial instanceof EditorialBlog) {
            return null;
        }

        $standfirst = $editorial->standFirst();

        if (null === $standfirst) {
            return null;
        }

        return [
            'title' => $standfirst->title(),
            'items' => $standfirst->items(),
        ];
    }
}
