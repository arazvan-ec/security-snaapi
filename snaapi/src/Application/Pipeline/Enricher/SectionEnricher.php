<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnricherInterface;
use App\Domain\Port\Gateway\SectionGatewayInterface;

/**
 * Enriches the context with the section data.
 *
 * Depends on editorial being set in context (requires EditorialEnricher to run first).
 */
final readonly class SectionEnricher implements EnricherInterface
{
    public function __construct(
        private SectionGatewayInterface $gateway,
    ) {
    }

    public function priority(): int
    {
        return 90;
    }

    public function supports(EditorialContext $context): bool
    {
        return null !== $context->editorial();
    }

    public function enrich(EditorialContext $context): void
    {
        $editorial = $context->editorial();

        if (null === $editorial) {
            return;
        }

        $section = $this->gateway->findById($editorial->sectionId());

        if (null !== $section) {
            $context->setSection($section);
        }
    }
}
