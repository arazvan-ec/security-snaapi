<?php

declare(strict_types=1);

namespace App\Application\Factory\Response;

use App\Application\Contract\Context\ContextInterface;
use App\Application\DTO\Response\EditorialResponse;
use App\Application\Result\Error;
use App\Application\Result\Result;
use App\Application\Service\Formatter\DateFormatterInterface;
use App\Application\Service\Formatter\TypeMapperInterface;
use App\Application\Strategy\Editorial\EditorialFieldExtractorInterface;
use Ec\Editorial\Domain\Model\EditorialBlog;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Editorial\Domain\Model\Signature;

/**
 * SOLID-compliant Factory for creating EditorialResponse.
 *
 * Refactored to follow SOLID principles:
 * - SRP: Delegates formatting to specialized services (DateFormatter, TypeMapper)
 * - OCP: New editorial types can be added via Strategy pattern (EditorialFieldExtractor)
 * - LSP: Uses proper polymorphism instead of method_exists()
 * - ISP: Depends on segregated context interfaces
 * - DIP: Depends on abstractions (interfaces) not concrete implementations
 *
 * This factory composes multiple specialized factories and services
 * to create the final response, following the Composite pattern.
 */
final readonly class SolidEditorialResponseFactory
{
    private const TWITTER_TYPES = [EditorialBlog::EDITORIAL_TYPE];

    public function __construct(
        private TitlesResponseFactory $titlesFactory,
        private BodyResponseFactory $bodyFactory,
        private SectionResponseFactory $sectionFactory,
        private TagResponseFactory $tagFactory,
        private SignatureResponseFactory $signatureFactory,
        private MultimediaResponseFactory $multimediaFactory,
        private DateFormatterInterface $dateFormatter,
        private TypeMapperInterface $typeMapper,
        private EditorialFieldExtractorInterface $fieldExtractor,
    ) {
    }

    /**
     * Create EditorialResponse using Result pattern for explicit error handling.
     *
     * @return Result<EditorialResponse, Error>
     */
    public function createResult(ContextInterface $context): Result
    {
        $editorial = $context->editorial();

        if (null === $editorial) {
            return Result::failure(
                Error::notFound('Editorial', $context->editorialId())
            );
        }

        try {
            $response = $this->buildResponse($editorial, $context);

            return Result::success($response);
        } catch (\Throwable $e) {
            return Result::failure(
                Error::internal('Failed to create editorial response', $e)
            );
        }
    }

    /**
     * Create EditorialResponse (throws exception on error).
     */
    public function create(ContextInterface $context): EditorialResponse
    {
        return $this->createResult($context)
            ->fold(
                fn (EditorialResponse $response) => $response,
                fn (Error $error) => throw new \RuntimeException($error->message())
            );
    }

    private function buildResponse(NewsBase $editorial, ContextInterface $context): EditorialResponse
    {
        $section = $context->section();
        $hasTwitter = \in_array($editorial->editorialType(), self::TWITTER_TYPES, true);
        $editorialType = $this->typeMapper->map($editorial->editorialType());

        $resolveData = [
            'photoFromBodyTags' => $context->bodyPhotos(),
            'insertedNews' => $context->get('insertedNews', []),
            'membershipLinkCombine' => $context->membershipLinks(),
        ];

        return new EditorialResponse(
            id: $editorial->id()->id(),
            url: $editorial->url(),
            titles: $this->titlesFactory->create($editorial->titles()),
            lead: $editorial->lead(),
            publicationDate: $this->dateFormatter->format($editorial->publicationDate()),
            updatedOn: $this->dateFormatter->formatNullable($editorial->updatedOn()),
            endOn: $this->dateFormatter->formatNullable(
                $this->fieldExtractor->extractEndOn($editorial)
            ),
            typeId: (string) $editorialType->id(),
            typeName: $editorialType->name(),
            indexable: $editorial->isIndexable(),
            deleted: $editorial->isDeleted(),
            published: $editorial->isVisible(),
            closingModeId: $this->fieldExtractor->extractClosingModeId($editorial),
            commentable: $editorial->isCommentable(),
            isBrand: $this->fieldExtractor->extractIsBrand($editorial),
            isAmazonOnsite: $this->fieldExtractor->extractIsAmazonOnsite($editorial),
            contentType: $this->fieldExtractor->extractContentType($editorial),
            canonicalEditorialId: $this->fieldExtractor->extractCanonicalEditorialId($editorial),
            urlDate: $this->dateFormatter->formatNullable(
                $this->fieldExtractor->extractUrlDate($editorial)
            ),
            countWords: $editorial->countWords(),
            body: $this->bodyFactory->create($editorial->body(), $resolveData),
            signatures: $this->createSignatures($editorial, $section, $hasTwitter, $context),
            section: null !== $section ? $this->sectionFactory->create($section) : null,
            tags: $this->createTags($context),
            countComments: $context->commentsCount(),
            multimedia: $this->createMultimedia($context),
            standfirst: $this->fieldExtractor->extractStandfirst($editorial),
            recommendedEditorials: $context->get('recommendedEditorials'),
        );
    }

    /**
     * @return \App\Application\DTO\Response\SignatureResponse[]
     */
    private function createSignatures(
        NewsBase $editorial,
        ?\Ec\Section\Domain\Model\Section $section,
        bool $hasTwitter,
        ContextInterface $context
    ): array {
        if (null === $section) {
            return [];
        }

        $signatures = [];

        /** @var Signature $signature */
        foreach ($editorial->signatures()->getArrayCopy() as $signature) {
            $aliasId = $signature->id()->id();
            $journalist = $context->journalist($aliasId);

            if (null !== $journalist) {
                $signatures[] = $this->signatureFactory->create(
                    $aliasId,
                    $journalist,
                    $section,
                    $hasTwitter
                );
            }
        }

        return $signatures;
    }

    /**
     * @return \App\Application\DTO\Response\TagResponse[]
     */
    private function createTags(ContextInterface $context): array
    {
        $tags = [];

        foreach ($context->tags() as $tag) {
            $tags[] = $this->tagFactory->create($tag);
        }

        return $tags;
    }

    private function createMultimedia(ContextInterface $context): ?\App\Application\DTO\Response\MultimediaResponse
    {
        $multimedia = $context->multimedia();

        if (null === $multimedia) {
            return null;
        }

        return $this->multimediaFactory->create($multimedia);
    }
}
