<?php

declare(strict_types=1);

namespace App\Orchestrator\Chain;

use App\Application\Factory\Response\EditorialResponseFactory;
use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnrichmentPipeline;
use App\Application\Strategy\Visibility\VisibilityStrategyInterface;
use Symfony\Component\HttpFoundation\Request;

final class PipelineEditorialOrchestrator implements EditorialOrchestratorInterface
{
    public function __construct(
        private readonly EnrichmentPipeline $pipeline,
        private readonly VisibilityStrategyInterface $visibilityStrategy,
        private readonly EditorialResponseFactory $responseFactory,
        private readonly string $contentType,
    ) {
    }

    public function execute(Request $request): array
    {
        $id = (string) $request->attributes->get('id', '');

        $context = new EditorialContext($id);
        $context = $this->pipeline->process($context);

        $editorial = $context->editorial();
        if (null === $editorial) {
            throw new \RuntimeException(sprintf('Editorial not found: %s', $id));
        }

        $this->visibilityStrategy->checkVisibility($editorial);

        $response = $this->responseFactory->create($context);

        return $response->jsonSerialize();
    }

    public function canOrchestrate(): string
    {
        return $this->contentType;
    }
}
