<?php

declare(strict_types=1);

namespace App\Application\Strategy\Editorial;

use Ec\Editorial\Domain\Model\NewsBase;

/**
 * Strategy: Default field extractor for standard editorials.
 *
 * Provides default (null/false) values for optional fields.
 * This is the fallback strategy when no specific extractor matches.
 */
final readonly class DefaultEditorialFieldExtractor implements EditorialFieldExtractorInterface
{
    public function supports(NewsBase $editorial): bool
    {
        // Default extractor supports all editorials as fallback
        return true;
    }

    public function priority(): int
    {
        return 0; // Lowest priority - used as fallback
    }

    public function extractEndOn(NewsBase $editorial): ?\DateTimeInterface
    {
        return null;
    }

    public function extractClosingModeId(NewsBase $editorial): ?string
    {
        return null;
    }

    public function extractIsBrand(NewsBase $editorial): bool
    {
        return false;
    }

    public function extractIsAmazonOnsite(NewsBase $editorial): bool
    {
        return false;
    }

    public function extractContentType(NewsBase $editorial): ?string
    {
        return null;
    }

    public function extractCanonicalEditorialId(NewsBase $editorial): ?string
    {
        return null;
    }

    public function extractUrlDate(NewsBase $editorial): ?\DateTimeInterface
    {
        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function extractStandfirst(NewsBase $editorial): ?array
    {
        return null;
    }
}
