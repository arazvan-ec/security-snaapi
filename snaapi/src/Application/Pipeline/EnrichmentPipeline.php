<?php

declare(strict_types=1);

namespace App\Application\Pipeline;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * Orchestrates the execution of enrichers in priority order.
 *
 * The pipeline:
 * 1. Sorts enrichers by priority (descending)
 * 2. Executes each enricher that supports the context
 * 3. Handles errors gracefully (logs and continues)
 * 4. Returns the enriched context
 */
final class EnrichmentPipeline
{
    /** @var EnricherInterface[] */
    private array $sortedEnrichers;

    /**
     * @param iterable<EnricherInterface> $enrichers
     */
    public function __construct(
        #[TaggedIterator('app.enricher')]
        private readonly iterable $enrichers,
        private readonly LoggerInterface $logger,
    ) {
        $this->sortedEnrichers = $this->sortByPriority($this->enrichers);
    }

    public function process(EditorialContext $context): EditorialContext
    {
        foreach ($this->sortedEnrichers as $enricher) {
            if (!$enricher->supports($context)) {
                $this->logger->debug('Enricher skipped (not supported)', [
                    'enricher' => $enricher::class,
                    'editorialId' => $context->editorialId(),
                ]);
                continue;
            }

            try {
                $enricher->enrich($context);
                $this->logger->info('Enrichment completed', [
                    'enricher' => $enricher::class,
                    'editorialId' => $context->editorialId(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Enricher failed', [
                    'enricher' => $enricher::class,
                    'editorialId' => $context->editorialId(),
                    'error' => $e->getMessage(),
                    'exception' => $e,
                ]);
                // Graceful degradation - continue with next enricher
            }
        }

        return $context;
    }

    /**
     * @param iterable<EnricherInterface> $enrichers
     *
     * @return EnricherInterface[]
     */
    private function sortByPriority(iterable $enrichers): array
    {
        $array = [...$enrichers];
        usort($array, static fn (EnricherInterface $a, EnricherInterface $b): int => $b->priority() <=> $a->priority());

        return $array;
    }
}
