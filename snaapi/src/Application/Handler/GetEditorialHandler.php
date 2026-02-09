<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\DTO\Response\EditorialResponse;
use App\Application\Factory\Response\EditorialResponseFactory;
use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnrichmentPipeline;

/**
 * Handler for getting an editorial by ID.
 *
 * Orchestrates the pipeline and factory to produce the response.
 */
final readonly class GetEditorialHandler
{
    public function __construct(
        private EnrichmentPipeline $pipeline,
        private EditorialResponseFactory $factory,
    ) {
    }

    public function __invoke(string $editorialId): EditorialResponse
    {
        $context = new EditorialContext($editorialId);
        $enrichedContext = $this->pipeline->process($context);

        return $this->factory->create($enrichedContext);
    }
}
