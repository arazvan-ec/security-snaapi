<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnricherInterface;
use App\Domain\Port\Gateway\EditorialGatewayInterface;
use App\Domain\Port\Gateway\JournalistGatewayInterface;
use App\Domain\Port\Gateway\SectionGatewayInterface;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\Signature;
use Psr\Log\LoggerInterface;

/**
 * Enriches the context with editorial data for inserted news body elements.
 *
 * Scans BodyTagInsertedNews elements in the editorial body and fetches
 * their referenced editorials, sections, and journalists.
 */
final readonly class InsertedNewsEnricher implements EnricherInterface
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
        return 55;
    }

    public function supports(EditorialContext $context): bool
    {
        $editorial = $context->editorial();

        return null !== $editorial && null !== $editorial->body();
    }

    public function enrich(EditorialContext $context): void
    {
        $editorial = $context->editorial();

        if (null === $editorial) {
            return;
        }

        $body = $editorial->body();

        if (null === $body) {
            return;
        }

        $insertedNews = [];

        /** @var BodyTagInsertedNews[] $elements */
        $elements = $body->bodyElementsOf(BodyTagInsertedNews::class);

        foreach ($elements as $element) {
            try {
                $this->processInsertedNewsElement($element, $insertedNews);
            } catch (\Throwable $exception) {
                $this->logger->error('Failed to fetch inserted news', [
                    'editorialId' => $element->editorialId()->id(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $context->setInsertedNews($insertedNews);
    }

    /**
     * @param array<string, array<string, mixed>> $insertedNews
     */
    private function processInsertedNewsElement(BodyTagInsertedNews $element, array &$insertedNews): void
    {
        $id = $element->editorialId()->id();

        $referencedEditorial = $this->editorialGateway->findById($id);

        if (null === $referencedEditorial || !$referencedEditorial->isVisible()) {
            return;
        }

        $section = $this->sectionGateway->findById($referencedEditorial->sectionId());

        $signatures = [];

        /** @var Signature $signature */
        foreach ($referencedEditorial->signatures()->getArrayCopy() as $signature) {
            $aliasId = $signature->id()->id();
            $journalist = $this->journalistGateway->findByAliasId($aliasId);

            if (null !== $journalist) {
                $signatures[$aliasId] = $journalist;
            }
        }

        $multimediaId = !empty($referencedEditorial->multimedia()->id()->id())
            ? $referencedEditorial->multimedia()->id()->id()
            : $referencedEditorial->metaImage();

        $insertedNews[$id] = [
            'editorial' => $referencedEditorial,
            'section' => $section,
            'signatures' => $signatures,
            'multimediaId' => $multimediaId,
        ];
    }
}
