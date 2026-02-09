<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnricherInterface;
use App\Domain\Port\Gateway\EditorialGatewayInterface;
use App\Domain\Port\Gateway\JournalistGatewayInterface;
use App\Domain\Port\Gateway\SectionGatewayInterface;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\Signature;
use Psr\Log\LoggerInterface;

/**
 * Enriches the context with recommended editorial data.
 *
 * Fetches full editorial, section, and journalist data for each
 * recommended editorial linked to the main editorial.
 */
final readonly class RecommendedEditorialsEnricher implements EnricherInterface
{
    public function __construct(
        private EditorialGatewayInterface $editorialGateway,
        private SectionGatewayInterface $sectionGateway,
        private JournalistGatewayInterface $journalistGateway,
        private LoggerInterface $logger,
    ) {
    }

    public function priority(): int
    {
        return 25;
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

        $recommendedEditorials = [];

        /** @var EditorialId $recommendedEditorialId */
        foreach ($editorial->recommendedEditorials()->editorialIds() as $recommendedEditorialId) {
            $id = $recommendedEditorialId->id();

            try {
                $recommendedEditorial = $this->editorialGateway->findById($id);

                if (null === $recommendedEditorial || !$recommendedEditorial->isVisible()) {
                    continue;
                }

                $section = $this->sectionGateway->findById($recommendedEditorial->sectionId());

                $signatures = [];

                /** @var Signature $signature */
                foreach ($recommendedEditorial->signatures()->getArrayCopy() as $signature) {
                    $aliasId = $signature->id()->id();
                    $journalist = $this->journalistGateway->findByAliasId($aliasId);

                    if (null !== $journalist) {
                        $signatures[$aliasId] = $journalist;
                    }
                }

                $multimediaId = !empty($recommendedEditorial->multimedia()->id()->id())
                    ? $recommendedEditorial->multimedia()->id()->id()
                    : $recommendedEditorial->metaImage();

                $recommendedEditorials[$id] = [
                    'editorial' => $recommendedEditorial,
                    'section' => $section,
                    'signatures' => $signatures,
                    'multimediaId' => $multimediaId,
                ];
            } catch (\Throwable $exception) {
                $this->logger->error('Failed to fetch recommended editorial', [
                    'editorialId' => $id,
                    'error' => $exception->getMessage(),
                ]);

                continue;
            }
        }

        $context->setRecommendedEditorials($recommendedEditorials);
    }
}
