<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Contract\Context\ContextInterface;
use App\Application\DTO\Response\EditorialResponse;
use App\Application\Factory\Response\SolidEditorialResponseFactory;
use App\Application\Pipeline\Context\EditorialPipelineContext;
use App\Application\Pipeline\SolidEnrichmentPipeline;
use App\Application\Result\Error;
use App\Application\Result\Result;

/**
 * SOLID-compliant Handler for getting an editorial by ID.
 *
 * Uses the Result pattern for explicit error handling, allowing callers
 * to decide how to handle errors instead of forcing exception handling.
 *
 * SRP: Only orchestrates the pipeline and factory.
 * DIP: Depends on abstractions (interfaces) for pipeline and factory.
 * OCP: Behavior can be extended via the pipeline's enrichers.
 */
final readonly class SolidGetEditorialHandler
{
    public function __construct(
        private SolidEnrichmentPipeline $pipeline,
        private SolidEditorialResponseFactory $factory,
    ) {
    }

    /**
     * Get editorial with explicit Result-based error handling.
     *
     * @return Result<EditorialResponse, Error>
     */
    public function handle(string $editorialId): Result
    {
        $context = EditorialPipelineContext::forEditorial($editorialId);

        return $this->pipeline
            ->process($context)
            ->flatMap(fn (ContextInterface $ctx) => $this->factory->createResult($ctx));
    }

    /**
     * Get editorial (throws exception on error).
     *
     * Use this when you prefer traditional exception handling.
     */
    public function __invoke(string $editorialId): EditorialResponse
    {
        return $this->handle($editorialId)
            ->fold(
                fn (EditorialResponse $response) => $response,
                fn (Error $error) => throw new \RuntimeException($error->message())
            );
    }
}
