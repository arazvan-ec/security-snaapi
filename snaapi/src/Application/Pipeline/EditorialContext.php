<?php

declare(strict_types=1);

namespace App\Application\Pipeline;

use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;

/**
 * Pipeline DTO that carries data through the enrichment pipeline.
 *
 * This context object accumulates data from various enrichers,
 * allowing each step to add its contribution without coupling.
 */
final class EditorialContext
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(
        private readonly string $editorialId,
    ) {
    }

    public function editorialId(): string
    {
        return $this->editorialId;
    }

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

    // Type-safe getters for common data

    public function editorial(): ?NewsBase
    {
        $editorial = $this->get('editorial');

        return $editorial instanceof NewsBase ? $editorial : null;
    }

    public function setEditorial(NewsBase $editorial): void
    {
        $this->set('editorial', $editorial);
    }

    public function section(): ?Section
    {
        $section = $this->get('section');

        return $section instanceof Section ? $section : null;
    }

    public function setSection(Section $section): void
    {
        $this->set('section', $section);
    }

    public function multimedia(): ?Multimedia
    {
        $multimedia = $this->get('multimedia');

        return $multimedia instanceof Multimedia ? $multimedia : null;
    }

    public function setMultimedia(Multimedia $multimedia): void
    {
        $this->set('multimedia', $multimedia);
    }

    /**
     * @return Tag[]
     */
    public function tags(): array
    {
        /** @var Tag[] $tags */
        $tags = $this->get('tags', []);

        return $tags;
    }

    /**
     * @param Tag[] $tags
     */
    public function setTags(array $tags): void
    {
        $this->set('tags', $tags);
    }

    /**
     * @return array<string, Journalist>
     */
    public function journalists(): array
    {
        /** @var array<string, Journalist> $journalists */
        $journalists = $this->get('journalists', []);

        return $journalists;
    }

    /**
     * @param array<string, Journalist> $journalists
     */
    public function setJournalists(array $journalists): void
    {
        $this->set('journalists', $journalists);
    }

    public function commentsCount(): int
    {
        /** @var int $count */
        $count = $this->get('commentsCount', 0);

        return $count;
    }

    public function setCommentsCount(int $count): void
    {
        $this->set('commentsCount', $count);
    }

    /**
     * @return array<string, string>
     */
    public function membershipLinks(): array
    {
        /** @var array<string, string> $links */
        $links = $this->get('membershipLinks', []);

        return $links;
    }

    /**
     * @param array<string, string> $links
     */
    public function setMembershipLinks(array $links): void
    {
        $this->set('membershipLinks', $links);
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
