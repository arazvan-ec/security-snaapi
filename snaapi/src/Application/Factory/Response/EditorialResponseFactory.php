<?php

declare(strict_types=1);

namespace App\Application\Factory\Response;

use App\Application\DTO\Response\EditorialResponse;
use App\Application\Pipeline\EditorialContext;
use Ec\Editorial\Domain\Model\EditorialBlog;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Editorial\Domain\Model\Signature;

/**
 * Factory for creating EditorialResponse from EditorialContext.
 */
final readonly class EditorialResponseFactory
{
    private const TWITTER_TYPES = [EditorialBlog::EDITORIAL_TYPE];

    public function __construct(
        private TitlesResponseFactory $titlesFactory,
        private BodyResponseFactory $bodyFactory,
        private SectionResponseFactory $sectionFactory,
        private TagResponseFactory $tagFactory,
        private SignatureResponseFactory $signatureFactory,
        private MultimediaResponseFactory $multimediaFactory,
    ) {
    }

    public function create(EditorialContext $context): EditorialResponse
    {
        $editorial = $context->editorial();

        if (null === $editorial) {
            throw new \RuntimeException('Editorial not found in context');
        }

        $section = $context->section();
        $hasTwitter = \in_array($editorial->editorialType(), self::TWITTER_TYPES, true);

        $resolveData = [
            'photoFromBodyTags' => $context->bodyPhotos(),
            'insertedNews' => $context->insertedNews(),
            'membershipLinkCombine' => $context->membershipLinks(),
        ];

        return new EditorialResponse(
            id: $editorial->id()->id(),
            url: $editorial->url(),
            titles: $this->titlesFactory->create($editorial->titles()),
            lead: $editorial->lead(),
            publicationDate: $this->formatDate($editorial->publicationDate()),
            updatedOn: $editorial->updatedOn() ? $this->formatDate($editorial->updatedOn()) : null,
            endOn: $this->getEndOn($editorial),
            typeId: (string) $editorial->editorialType(),
            typeName: $this->getTypeName($editorial->editorialType()),
            indexable: $editorial->isIndexable(),
            deleted: $editorial->isDeleted(),
            published: $editorial->isVisible(),
            closingModeId: $this->getClosingModeId($editorial),
            commentable: $editorial->isCommentable(),
            isBrand: $this->getIsBrand($editorial),
            isAmazonOnsite: $this->getIsAmazonOnsite($editorial),
            contentType: $this->getContentType($editorial),
            canonicalEditorialId: $this->getCanonicalEditorialId($editorial),
            urlDate: $this->getUrlDate($editorial),
            countWords: $editorial->countWords(),
            body: $this->bodyFactory->create($editorial->body(), $resolveData),
            signatures: $this->createSignatures($editorial, $section, $hasTwitter, $context),
            section: null !== $section ? $this->sectionFactory->create($section) : null,
            tags: $this->createTags($context),
            countComments: $context->commentsCount(),
            multimedia: $this->createMultimedia($context),
            standfirst: $this->createStandfirst($editorial),
            recommendedEditorials: $context->recommendedEditorials(),
        );
    }

    private function formatDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    private function getEndOn(NewsBase $editorial): ?string
    {
        if (!method_exists($editorial, 'endOn')) {
            return null;
        }

        $endOn = $editorial->endOn();

        return $endOn instanceof \DateTimeInterface ? $this->formatDate($endOn) : null;
    }

    private function getTypeName(int $type): string
    {
        return match ($type) {
            1 => 'news',
            2 => 'blog',
            default => 'unknown',
        };
    }

    private function getClosingModeId(NewsBase $editorial): ?string
    {
        if (!method_exists($editorial, 'closingModeId')) {
            return null;
        }

        return $editorial->closingModeId();
    }

    private function getIsBrand(NewsBase $editorial): bool
    {
        if (!method_exists($editorial, 'isBrand')) {
            return false;
        }

        return $editorial->isBrand();
    }

    private function getIsAmazonOnsite(NewsBase $editorial): bool
    {
        if (!method_exists($editorial, 'isAmazonOnsite')) {
            return false;
        }

        return $editorial->isAmazonOnsite();
    }

    private function getContentType(NewsBase $editorial): ?string
    {
        if (!method_exists($editorial, 'contentType')) {
            return null;
        }

        return $editorial->contentType();
    }

    private function getCanonicalEditorialId(NewsBase $editorial): ?string
    {
        if (!method_exists($editorial, 'canonicalEditorialId')) {
            return null;
        }

        return $editorial->canonicalEditorialId()?->id();
    }

    private function getUrlDate(NewsBase $editorial): ?string
    {
        if (!method_exists($editorial, 'urlDate')) {
            return null;
        }

        $urlDate = $editorial->urlDate();

        return $urlDate instanceof \DateTimeInterface ? $this->formatDate($urlDate) : null;
    }

    /**
     * @return \App\Application\DTO\Response\SignatureResponse[]
     */
    private function createSignatures(
        NewsBase $editorial,
        ?\Ec\Section\Domain\Model\Section $section,
        bool $hasTwitter,
        EditorialContext $context
    ): array {
        if (null === $section) {
            return [];
        }

        $signatures = [];
        $journalists = $context->journalists();

        /** @var Signature $signature */
        foreach ($editorial->signatures()->getArrayCopy() as $signature) {
            $aliasId = $signature->id()->id();
            $journalist = $journalists[$aliasId] ?? null;

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
    private function createTags(EditorialContext $context): array
    {
        $tags = [];

        foreach ($context->tags() as $tag) {
            $tags[] = $this->tagFactory->create($tag);
        }

        return $tags;
    }

    private function createMultimedia(EditorialContext $context): ?\App\Application\DTO\Response\MultimediaResponse
    {
        $multimedia = $context->multimedia();

        if (null === $multimedia) {
            return null;
        }

        return $this->multimediaFactory->create($multimedia);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function createStandfirst(NewsBase $editorial): ?array
    {
        if (!method_exists($editorial, 'standFirst')) {
            return null;
        }

        $standfirst = $editorial->standFirst();

        if (null === $standfirst) {
            return null;
        }

        return [
            'title' => $standfirst->title(),
            'items' => $standfirst->items(),
        ];
    }
}
