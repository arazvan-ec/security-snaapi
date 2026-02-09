<?php

declare(strict_types=1);

namespace App\Application\Strategy\Editorial;

use Ec\Editorial\Domain\Model\NewsBase;

/**
 * Strategy Pattern: Interface for extracting optional fields from editorials.
 *
 * LSP: Eliminates method_exists() checks by using proper polymorphism.
 * OCP: New field extractors can be added without modifying existing code.
 *
 * Each implementation handles extraction for a specific editorial type,
 * providing a clean, type-safe way to access optional fields.
 */
interface EditorialFieldExtractorInterface
{
    /**
     * Check if this extractor supports the given editorial type.
     */
    public function supports(NewsBase $editorial): bool;

    /**
     * Get the priority of this extractor (higher = checked first).
     */
    public function priority(): int;

    /**
     * Extract the end date if available.
     */
    public function extractEndOn(NewsBase $editorial): ?\DateTimeInterface;

    /**
     * Extract the closing mode ID if available.
     */
    public function extractClosingModeId(NewsBase $editorial): ?string;

    /**
     * Extract the brand flag if available.
     */
    public function extractIsBrand(NewsBase $editorial): bool;

    /**
     * Extract the Amazon onsite flag if available.
     */
    public function extractIsAmazonOnsite(NewsBase $editorial): bool;

    /**
     * Extract the content type if available.
     */
    public function extractContentType(NewsBase $editorial): ?string;

    /**
     * Extract the canonical editorial ID if available.
     */
    public function extractCanonicalEditorialId(NewsBase $editorial): ?string;

    /**
     * Extract the URL date if available.
     */
    public function extractUrlDate(NewsBase $editorial): ?\DateTimeInterface;

    /**
     * Extract the standfirst if available.
     *
     * @return array<string, mixed>|null
     */
    public function extractStandfirst(NewsBase $editorial): ?array;
}
