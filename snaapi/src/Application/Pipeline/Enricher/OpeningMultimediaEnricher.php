<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnricherInterface;
use App\Domain\Port\Gateway\MultimediaGatewayInterface;
use App\Orchestrator\Chain\Multimedia\MultimediaOrchestratorHandler;
use Psr\Log\LoggerInterface;

/**
 * Enriches the context with opening multimedia data.
 *
 * Fetches the opening multimedia for the editorial and processes it
 * through the MultimediaOrchestratorHandler for type-specific handling.
 */
final readonly class OpeningMultimediaEnricher implements EnricherInterface
{
    public function __construct(
        private MultimediaGatewayInterface $gateway,
        private MultimediaOrchestratorHandler $multimediaOrchestratorHandler,
        private LoggerInterface $logger,
    ) {
    }

    public function priority(): int
    {
        return 75;
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

        $opening = $editorial->opening();

        if (null === $opening || empty($opening->multimediaId())) {
            return;
        }

        $multimedia = $this->gateway->findOpeningMultimediaById($opening->multimediaId());

        if (null === $multimedia) {
            return;
        }

        try {
            $result = $this->multimediaOrchestratorHandler->handler($multimedia);
            $context->setMultimediaOpening($result);
        } catch (\Throwable $exception) {
            $this->logger->warning('Failed to process opening multimedia', [
                'multimediaId' => $opening->multimediaId(),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
