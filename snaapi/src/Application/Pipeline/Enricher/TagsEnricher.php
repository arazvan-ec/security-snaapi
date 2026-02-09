<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnricherInterface;
use App\Domain\Port\Gateway\TagGatewayInterface;

/**
 * Enriches the context with tag data.
 */
final readonly class TagsEnricher implements EnricherInterface
{
    public function __construct(
        private TagGatewayInterface $gateway,
    ) {
    }

    public function priority(): int
    {
        return 70;
    }

    public function supports(EditorialContext $context): bool
    {
        $editorial = $context->editorial();

        return null !== $editorial && $editorial->tags()->count() > 0;
    }

    public function enrich(EditorialContext $context): void
    {
        $editorial = $context->editorial();

        if (null === $editorial) {
            return;
        }

        $tagIds = [];
        foreach ($editorial->tags()->getArrayCopy() as $tag) {
            $tagIds[] = $tag->id();
        }

        $tags = $this->gateway->findByIds($tagIds);
        $context->setTags($tags);
    }
}
