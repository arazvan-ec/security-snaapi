<?php

declare(strict_types=1);

namespace App\Application\Pipeline;

/**
 * Interface for pipeline enrichers.
 *
 * Each enricher is responsible for a single concern:
 * - Fetching data from a specific gateway
 * - Adding that data to the context
 *
 * Enrichers are executed in priority order (highest first).
 */
interface EnricherInterface
{
    /**
     * Returns the priority of this enricher.
     *
     * Higher values execute first.
     * Recommended ranges:
     * - 100: Core data (editorial)
     * - 90-80: Direct dependencies (multimedia, section)
     * - 70-60: Secondary data (tags, journalists)
     * - 50-40: Auxiliary data (comments, membership)
     */
    public function priority(): int;

    /**
     * Determines if this enricher should run for the given context.
     *
     * Use this to skip enrichers based on editorial type or other conditions.
     */
    public function supports(EditorialContext $context): bool;

    /**
     * Enriches the context with additional data.
     *
     * This method should:
     * - Fetch data from its gateway
     * - Add the data to the context using type-safe setters
     * - Handle gracefully if data is not available
     *
     * @throws \Throwable If a critical error occurs (will be caught by pipeline)
     */
    public function enrich(EditorialContext $context): void;
}
