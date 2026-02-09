<?php

declare(strict_types=1);

namespace App\Application\Strategy\Editorial;

use Ec\Editorial\Domain\Model\NewsBase;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * Chain of Responsibility + Strategy Pattern: Orchestrates field extractors.
 *
 * OCP: New extractors can be added by creating a new class and tagging it.
 * DIP: Depends on the abstraction (interface), not concrete implementations.
 *
 * This class replaces all method_exists() checks in EditorialResponseFactory
 * with a clean, extensible, type-safe approach.
 */
final class EditorialFieldExtractorChain implements EditorialFieldExtractorInterface
{
    /** @var EditorialFieldExtractorInterface[] */
    private readonly array $extractors;

    /**
     * @param iterable<EditorialFieldExtractorInterface> $extractors
     */
    public function __construct(
        #[TaggedIterator('app.editorial_field_extractor')]
        iterable $extractors,
    ) {
        $extractorArray = [...$extractors];

        // Sort by priority (descending)
        usort(
            $extractorArray,
            static fn (EditorialFieldExtractorInterface $a, EditorialFieldExtractorInterface $b): int => $b->priority() <=> $a->priority()
        );

        $this->extractors = $extractorArray;
    }

    public function supports(NewsBase $editorial): bool
    {
        return true; // Chain always supports all editorials
    }

    public function priority(): int
    {
        return 0;
    }

    public function extractEndOn(NewsBase $editorial): ?\DateTimeInterface
    {
        return $this->findExtractor($editorial)->extractEndOn($editorial);
    }

    public function extractClosingModeId(NewsBase $editorial): ?string
    {
        return $this->findExtractor($editorial)->extractClosingModeId($editorial);
    }

    public function extractIsBrand(NewsBase $editorial): bool
    {
        return $this->findExtractor($editorial)->extractIsBrand($editorial);
    }

    public function extractIsAmazonOnsite(NewsBase $editorial): bool
    {
        return $this->findExtractor($editorial)->extractIsAmazonOnsite($editorial);
    }

    public function extractContentType(NewsBase $editorial): ?string
    {
        return $this->findExtractor($editorial)->extractContentType($editorial);
    }

    public function extractCanonicalEditorialId(NewsBase $editorial): ?string
    {
        return $this->findExtractor($editorial)->extractCanonicalEditorialId($editorial);
    }

    public function extractUrlDate(NewsBase $editorial): ?\DateTimeInterface
    {
        return $this->findExtractor($editorial)->extractUrlDate($editorial);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function extractStandfirst(NewsBase $editorial): ?array
    {
        return $this->findExtractor($editorial)->extractStandfirst($editorial);
    }

    private function findExtractor(NewsBase $editorial): EditorialFieldExtractorInterface
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->supports($editorial)) {
                return $extractor;
            }
        }

        // This should never happen if DefaultEditorialFieldExtractor is registered
        throw new \RuntimeException('No field extractor found for editorial');
    }
}
