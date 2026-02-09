<?php

declare(strict_types=1);

namespace App\Application\Pipeline;

use App\Application\Pipeline\Context\EditorialPipelineContext;
use App\Application\Result\Error;
use App\Application\Result\Result;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * SOLID-compliant Enrichment Pipeline.
 *
 * Improvements over the original:
 * - Uses Result pattern for explicit error handling
 * - Uses the new ISP-compliant context
 * - Better error aggregation and reporting
 *
 * OCP: New enrichers can be added without modifying this class.
 * DIP: Depends on EnricherInterface abstraction.
 * SRP: Only orchestrates enricher execution.
 */
final class SolidEnrichmentPipeline
{
    /** @var EnricherInterface[] */
    private readonly array $sortedEnrichers;

    /**
     * @param iterable<EnricherInterface> $enrichers
     */
    public function __construct(
        #[TaggedIterator('app.enricher')]
        iterable $enrichers,
        private readonly LoggerInterface $logger,
    ) {
        $this->sortedEnrichers = $this->sortByPriority($enrichers);
    }

    /**
     * Process the context through all enrichers.
     *
     * @return Result<EditorialPipelineContext, Error>
     */
    public function process(EditorialPipelineContext $context): Result
    {
        $errors = [];

        foreach ($this->sortedEnrichers as $enricher) {
            if (!$this->supportsContext($enricher, $context)) {
                $this->logSkipped($enricher, $context);
                continue;
            }

            $result = $this->executeEnricher($enricher, $context);

            if ($result->isFailure()) {
                $error = $result->getError();

                // Critical enrichers (like EditorialEnricher) should stop the pipeline
                if ($this->isCriticalEnricher($enricher)) {
                    return Result::failure($error);
                }

                $errors[] = $error;
                $this->logger->warning('Non-critical enricher failed, continuing', [
                    'enricher' => $enricher::class,
                    'error' => $error->message(),
                ]);
            }
        }

        if ([] !== $errors) {
            $this->logger->info('Pipeline completed with warnings', [
                'editorialId' => $context->editorialId(),
                'errorCount' => \count($errors),
            ]);
        }

        return Result::success($context);
    }

    /**
     * Process with strict mode - fail on any enricher error.
     *
     * @return Result<EditorialPipelineContext, Error>
     */
    public function processStrict(EditorialPipelineContext $context): Result
    {
        foreach ($this->sortedEnrichers as $enricher) {
            if (!$this->supportsContext($enricher, $context)) {
                continue;
            }

            $result = $this->executeEnricher($enricher, $context);

            if ($result->isFailure()) {
                return $result;
            }
        }

        return Result::success($context);
    }

    /**
     * @return Result<EditorialPipelineContext, Error>
     */
    private function executeEnricher(EnricherInterface $enricher, EditorialPipelineContext $context): Result
    {
        try {
            // The enricher modifies the context in place
            $enricher->enrich($context);

            $this->logger->info('Enrichment completed', [
                'enricher' => $enricher::class,
                'editorialId' => $context->editorialId(),
            ]);

            return Result::success($context);
        } catch (\Throwable $e) {
            $this->logger->error('Enricher failed', [
                'enricher' => $enricher::class,
                'editorialId' => $context->editorialId(),
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return Result::failure(
                Error::internal(
                    sprintf('Enricher %s failed: %s', $enricher::class, $e->getMessage()),
                    $e
                )
            );
        }
    }

    private function supportsContext(EnricherInterface $enricher, EditorialPipelineContext $context): bool
    {
        // We need to adapt from EditorialPipelineContext to EditorialContext
        // For now, we create a temporary adapter
        $legacyContext = $this->adaptToLegacyContext($context);

        return $enricher->supports($legacyContext);
    }

    private function adaptToLegacyContext(EditorialPipelineContext $context): EditorialContext
    {
        $legacyContext = new EditorialContext($context->editorialId());

        // Copy all data from new context to legacy context
        if ($context->hasEditorial()) {
            $legacyContext->setEditorial($context->editorial());
        }

        if ($context->hasSection()) {
            $legacyContext->setSection($context->section());
        }

        if ($context->hasMultimedia()) {
            $legacyContext->setMultimedia($context->multimedia());
        }

        $legacyContext->setTags($context->tags());
        $legacyContext->setJournalists($context->journalists());
        $legacyContext->setCommentsCount($context->commentsCount());
        $legacyContext->setMembershipLinks($context->membershipLinks());
        $legacyContext->setMultimediaOpening($context->multimediaOpening());
        $legacyContext->setBodyPhotos($context->bodyPhotos());
        $legacyContext->setInsertedNews($context->insertedNews());
        $legacyContext->setRecommendedEditorials($context->recommendedEditorials());

        return $legacyContext;
    }

    private function isCriticalEnricher(EnricherInterface $enricher): bool
    {
        // EditorialEnricher is critical - without the editorial, we can't proceed
        return $enricher->priority() >= 100;
    }

    private function logSkipped(EnricherInterface $enricher, EditorialPipelineContext $context): void
    {
        $this->logger->debug('Enricher skipped (not supported)', [
            'enricher' => $enricher::class,
            'editorialId' => $context->editorialId(),
        ]);
    }

    /**
     * @param iterable<EnricherInterface> $enrichers
     *
     * @return EnricherInterface[]
     */
    private function sortByPriority(iterable $enrichers): array
    {
        $array = [...$enrichers];
        usort(
            $array,
            static fn (EnricherInterface $a, EnricherInterface $b): int => $b->priority() <=> $a->priority()
        );

        return $array;
    }
}
