<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnricherInterface;
use App\Domain\Port\Gateway\MultimediaGatewayInterface;

/**
 * Enriches the context with multimedia data.
 */
final readonly class MultimediaEnricher implements EnricherInterface
{
    public function __construct(
        private MultimediaGatewayInterface $gateway,
    ) {
    }

    public function priority(): int
    {
        return 80;
    }

    public function supports(EditorialContext $context): bool
    {
        $editorial = $context->editorial();

        if (null === $editorial) {
            return false;
        }

        return !empty($editorial->multimedia()->id()->id());
    }

    public function enrich(EditorialContext $context): void
    {
        $editorial = $context->editorial();

        if (null === $editorial) {
            return;
        }

        $multimediaId = $editorial->multimedia()->id()->id();

        if (empty($multimediaId)) {
            return;
        }

        $multimedia = $this->gateway->findById($multimediaId);

        if (null !== $multimedia) {
            $context->setMultimedia($multimedia);
        }
    }
}
