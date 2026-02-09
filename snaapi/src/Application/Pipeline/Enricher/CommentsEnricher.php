<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnricherInterface;
use App\Domain\Port\Gateway\CommentGatewayInterface;

/**
 * Enriches the context with comment count.
 */
final readonly class CommentsEnricher implements EnricherInterface
{
    public function __construct(
        private CommentGatewayInterface $commentGateway,
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
        $count = $this->commentGateway->findCommentCountByEditorialId($context->editorialId());
        $context->setCommentsCount($count);
    }
}
