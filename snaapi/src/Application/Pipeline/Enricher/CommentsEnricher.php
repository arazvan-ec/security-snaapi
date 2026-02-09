<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnricherInterface;
use App\Ec\Snaapi\Infrastructure\Client\Http\QueryLegacyClient;

/**
 * Enriches the context with comment count.
 */
final readonly class CommentsEnricher implements EnricherInterface
{
    public function __construct(
        private QueryLegacyClient $legacyClient,
    ) {
    }

    public function priority(): int
    {
        return 40;
    }

    public function supports(EditorialContext $context): bool
    {
        return null !== $context->editorial();
    }

    public function enrich(EditorialContext $context): void
    {
        try {
            /** @var array{options: array{totalrecords?: int}} $comments */
            $comments = $this->legacyClient->findCommentsByEditorialId($context->editorialId());
            $count = $comments['options']['totalrecords'] ?? 0;
            $context->setCommentsCount($count);
        } catch (\Throwable) {
            $context->setCommentsCount(0);
        }
    }
}
