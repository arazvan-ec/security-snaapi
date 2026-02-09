<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

/**
 * Main DTO for the editorial API response.
 */
final readonly class EditorialResponse implements \JsonSerializable
{
    /**
     * @param SignatureResponse[] $signatures
     * @param TagResponse[] $tags
     * @param array<string, mixed>|null $standfirst
     * @param array<string, mixed>|null $recommendedEditorials
     */
    public function __construct(
        public string $id,
        public string $url,
        public TitlesResponse $titles,
        public string $lead,
        public string $publicationDate,
        public ?string $updatedOn,
        public ?string $endOn,
        public string $typeId,
        public string $typeName,
        public bool $indexable,
        public bool $deleted,
        public bool $published,
        public ?string $closingModeId,
        public bool $commentable,
        public bool $isBrand,
        public bool $isAmazonOnsite,
        public ?string $contentType,
        public ?string $canonicalEditorialId,
        public ?string $urlDate,
        public int $countWords,
        public BodyResponse $body,
        public array $signatures,
        public ?SectionResponse $section,
        public array $tags,
        public int $countComments,
        public ?MultimediaResponse $multimedia = null,
        public ?array $standfirst = null,
        public ?array $recommendedEditorials = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'id' => $this->id,
            'url' => $this->url,
            'titles' => $this->titles,
            'lead' => $this->lead,
            'publicationDate' => $this->publicationDate,
            'updatedOn' => $this->updatedOn,
            'endOn' => $this->endOn,
            'type' => [
                'id' => $this->typeId,
                'name' => $this->typeName,
            ],
            'indexable' => $this->indexable,
            'deleted' => $this->deleted,
            'published' => $this->published,
            'closingModeId' => $this->closingModeId,
            'commentable' => $this->commentable,
            'isBrand' => $this->isBrand,
            'isAmazonOnsite' => $this->isAmazonOnsite,
            'contentType' => $this->contentType,
            'canonicalEditorialId' => $this->canonicalEditorialId,
            'urlDate' => $this->urlDate,
            'countWords' => $this->countWords,
            'body' => $this->body,
            'multimedia' => $this->multimedia,
            'signatures' => $this->signatures,
            'section' => $this->section,
            'tags' => $this->tags,
            'countComments' => $this->countComments,
            'standfirst' => $this->standfirst,
            'recommendedEditorials' => $this->recommendedEditorials,
        ], fn ($v) => null !== $v);
    }
}
