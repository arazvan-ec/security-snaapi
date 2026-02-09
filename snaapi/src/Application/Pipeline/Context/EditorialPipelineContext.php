<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Context;

use App\Application\Contract\Context\ContextInterface;
use App\Domain\ValueObject\EditorialId;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;

/**
 * ISP-compliant Pipeline Context that implements segregated interfaces.
 *
 * This context implements all the segregated interfaces from the Contract layer,
 * allowing clients to depend only on the interfaces they need.
 *
 * ISP: Clients can depend on specific interfaces (HasEditorialInterface, HasTagsInterface, etc.)
 * instead of this entire class.
 *
 * SRP: This context only manages data flow through the pipeline; it doesn't perform
 * any business logic or transformations.
 */
final class EditorialPipelineContext implements ContextInterface
{
    /** @var array<string, mixed> */
    private array $data = [];

    private function __construct(
        private readonly EditorialId $editorialId,
    ) {
    }

    public static function forEditorial(string $editorialId): self
    {
        return new self(EditorialId::fromString($editorialId));
    }

    public function editorialId(): string
    {
        return $this->editorialId->value();
    }

    public function editorialIdValueObject(): EditorialId
    {
        return $this->editorialId;
    }

    // =================================================================
    // Generic Data Access
    // =================================================================

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    // =================================================================
    // HasEditorialInterface Implementation
    // =================================================================

    public function editorial(): ?NewsBase
    {
        $editorial = $this->get('editorial');

        return $editorial instanceof NewsBase ? $editorial : null;
    }

    public function hasEditorial(): bool
    {
        return null !== $this->editorial();
    }

    public function setEditorial(NewsBase $editorial): void
    {
        $this->set('editorial', $editorial);
    }

    // =================================================================
    // HasSectionInterface Implementation
    // =================================================================

    public function section(): ?Section
    {
        $section = $this->get('section');

        return $section instanceof Section ? $section : null;
    }

    public function hasSection(): bool
    {
        return null !== $this->section();
    }

    public function setSection(Section $section): void
    {
        $this->set('section', $section);
    }

    // =================================================================
    // HasMultimediaInterface Implementation
    // =================================================================

    public function multimedia(): ?Multimedia
    {
        $multimedia = $this->get('multimedia');

        return $multimedia instanceof Multimedia ? $multimedia : null;
    }

    public function hasMultimedia(): bool
    {
        return null !== $this->multimedia();
    }

    public function setMultimedia(Multimedia $multimedia): void
    {
        $this->set('multimedia', $multimedia);
    }

    /**
     * @return array<string, mixed>
     */
    public function multimediaOpening(): array
    {
        /** @var array<string, mixed> $opening */
        $opening = $this->get('multimediaOpening', []);

        return $opening;
    }

    /**
     * @param array<string, mixed> $opening
     */
    public function setMultimediaOpening(array $opening): void
    {
        $this->set('multimediaOpening', $opening);
    }

    /**
     * @return array<string, mixed>
     */
    public function bodyPhotos(): array
    {
        /** @var array<string, mixed> $photos */
        $photos = $this->get('bodyPhotos', []);

        return $photos;
    }

    /**
     * @param array<string, mixed> $photos
     */
    public function setBodyPhotos(array $photos): void
    {
        $this->set('bodyPhotos', $photos);
    }

    // =================================================================
    // HasTagsInterface Implementation
    // =================================================================

    /**
     * @return Tag[]
     */
    public function tags(): array
    {
        /** @var Tag[] $tags */
        $tags = $this->get('tags', []);

        return $tags;
    }

    public function hasTags(): bool
    {
        return [] !== $this->tags();
    }

    /**
     * @param Tag[] $tags
     */
    public function setTags(array $tags): void
    {
        $this->set('tags', $tags);
    }

    // =================================================================
    // HasJournalistsInterface Implementation
    // =================================================================

    /**
     * @return array<string, Journalist>
     */
    public function journalists(): array
    {
        /** @var array<string, Journalist> $journalists */
        $journalists = $this->get('journalists', []);

        return $journalists;
    }

    public function hasJournalists(): bool
    {
        return [] !== $this->journalists();
    }

    public function journalist(string $aliasId): ?Journalist
    {
        return $this->journalists()[$aliasId] ?? null;
    }

    /**
     * @param array<string, Journalist> $journalists
     */
    public function setJournalists(array $journalists): void
    {
        $this->set('journalists', $journalists);
    }

    // =================================================================
    // HasCommentsInterface Implementation
    // =================================================================

    public function commentsCount(): int
    {
        /** @var int $count */
        $count = $this->get('commentsCount', 0);

        return $count;
    }

    public function hasComments(): bool
    {
        return $this->commentsCount() > 0;
    }

    public function setCommentsCount(int $count): void
    {
        $this->set('commentsCount', $count);
    }

    // =================================================================
    // HasMembershipInterface Implementation
    // =================================================================

    /**
     * @return array<string, string>
     */
    public function membershipLinks(): array
    {
        /** @var array<string, string> $links */
        $links = $this->get('membershipLinks', []);

        return $links;
    }

    public function hasMembershipLinks(): bool
    {
        return [] !== $this->membershipLinks();
    }

    /**
     * @param array<string, string> $links
     */
    public function setMembershipLinks(array $links): void
    {
        $this->set('membershipLinks', $links);
    }

    // =================================================================
    // Additional Context Data
    // =================================================================

    /**
     * @return array<string, array<string, mixed>>
     */
    public function insertedNews(): array
    {
        /** @var array<string, array<string, mixed>> $news */
        $news = $this->get('insertedNews', []);

        return $news;
    }

    /**
     * @param array<string, array<string, mixed>> $news
     */
    public function setInsertedNews(array $news): void
    {
        $this->set('insertedNews', $news);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function recommendedEditorials(): array
    {
        /** @var array<string, array<string, mixed>> $editorials */
        $editorials = $this->get('recommendedEditorials', []);

        return $editorials;
    }

    /**
     * @param array<string, array<string, mixed>> $editorials
     */
    public function setRecommendedEditorials(array $editorials): void
    {
        $this->set('recommendedEditorials', $editorials);
    }
}
