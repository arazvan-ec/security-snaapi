<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnricherInterface;
use App\Domain\Port\Gateway\JournalistGatewayInterface;
use Ec\Editorial\Domain\Model\Signature;

/**
 * Enriches the context with journalist data.
 */
final readonly class JournalistsEnricher implements EnricherInterface
{
    public function __construct(
        private JournalistGatewayInterface $gateway,
    ) {
    }

    public function priority(): int
    {
        return 60;
    }

    public function supports(EditorialContext $context): bool
    {
        $editorial = $context->editorial();

        return null !== $editorial && $editorial->signatures()->count() > 0;
    }

    public function enrich(EditorialContext $context): void
    {
        $editorial = $context->editorial();

        if (null === $editorial) {
            return;
        }

        $journalists = [];

        /** @var Signature $signature */
        foreach ($editorial->signatures()->getArrayCopy() as $signature) {
            $aliasId = $signature->id()->id();
            $journalist = $this->gateway->findByAliasId($aliasId);

            if (null !== $journalist) {
                $journalists[$aliasId] = $journalist;
            }
        }

        $context->setJournalists($journalists);
    }
}
