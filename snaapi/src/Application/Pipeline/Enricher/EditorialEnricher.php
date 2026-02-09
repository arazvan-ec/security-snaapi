<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnricherInterface;
use App\Domain\Port\Gateway\EditorialGatewayInterface;
use App\Exception\EditorialNotPublishedYetException;

/**
 * Enriches the context with the editorial data.
 *
 * This is the primary enricher that fetches the main editorial.
 * It runs first (priority 100) as other enrichers depend on it.
 */
final readonly class EditorialEnricher implements EnricherInterface
{
    public function __construct(
        private EditorialGatewayInterface $gateway,
    ) {
    }

    public function priority(): int
    {
        return 100;
    }

    public function supports(EditorialContext $context): bool
    {
        return true;
    }

    public function enrich(EditorialContext $context): void
    {
        $editorial = $this->gateway->findById($context->editorialId());

        if (null === $editorial) {
            throw new \RuntimeException(sprintf('Editorial not found: %s', $context->editorialId()));
        }

        if (!$editorial->isVisible()) {
            throw new EditorialNotPublishedYetException();
        }

        $context->setEditorial($editorial);
    }
}
