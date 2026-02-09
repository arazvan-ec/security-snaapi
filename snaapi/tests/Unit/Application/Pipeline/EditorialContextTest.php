<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Pipeline;

use App\Application\Pipeline\EditorialContext;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EditorialContext::class)]
final class EditorialContextTest extends TestCase
{
    #[Test]
    public function it_stores_editorial_id(): void
    {
        $context = new EditorialContext('123');

        self::assertSame('123', $context->editorialId());
    }

    #[Test]
    public function it_sets_and_gets_arbitrary_data(): void
    {
        $context = new EditorialContext('123');

        $context->set('customKey', 'customValue');

        self::assertSame('customValue', $context->get('customKey'));
    }

    #[Test]
    public function it_returns_default_for_missing_key(): void
    {
        $context = new EditorialContext('123');

        self::assertNull($context->get('missing'));
        self::assertSame('default', $context->get('missing', 'default'));
    }

    #[Test]
    public function it_checks_if_key_exists(): void
    {
        $context = new EditorialContext('123');

        self::assertFalse($context->has('key'));

        $context->set('key', 'value');

        self::assertTrue($context->has('key'));
    }

    #[Test]
    public function it_handles_editorial(): void
    {
        $context = new EditorialContext('123');
        $editorial = $this->createMock(NewsBase::class);

        self::assertNull($context->editorial());

        $context->setEditorial($editorial);

        self::assertSame($editorial, $context->editorial());
    }

    #[Test]
    public function it_returns_null_when_editorial_is_wrong_type(): void
    {
        $context = new EditorialContext('123');
        $context->set('editorial', 'not-an-editorial');

        self::assertNull($context->editorial());
    }

    #[Test]
    public function it_handles_section(): void
    {
        $context = new EditorialContext('123');
        $section = $this->createMock(Section::class);

        self::assertNull($context->section());

        $context->setSection($section);

        self::assertSame($section, $context->section());
    }

    #[Test]
    public function it_returns_null_when_section_is_wrong_type(): void
    {
        $context = new EditorialContext('123');
        $context->set('section', 'not-a-section');

        self::assertNull($context->section());
    }

    #[Test]
    public function it_handles_multimedia(): void
    {
        $context = new EditorialContext('123');
        $multimedia = $this->createMock(Multimedia::class);

        self::assertNull($context->multimedia());

        $context->setMultimedia($multimedia);

        self::assertSame($multimedia, $context->multimedia());
    }

    #[Test]
    public function it_returns_null_when_multimedia_is_wrong_type(): void
    {
        $context = new EditorialContext('123');
        $context->set('multimedia', 'not-multimedia');

        self::assertNull($context->multimedia());
    }

    #[Test]
    public function it_handles_tags(): void
    {
        $context = new EditorialContext('123');
        $tag = $this->createMock(Tag::class);

        self::assertSame([], $context->tags());

        $context->setTags([$tag]);

        self::assertSame([$tag], $context->tags());
    }

    #[Test]
    public function it_handles_journalists(): void
    {
        $context = new EditorialContext('123');
        $journalist = $this->createMock(Journalist::class);

        self::assertSame([], $context->journalists());

        $context->setJournalists(['alias1' => $journalist]);

        self::assertSame(['alias1' => $journalist], $context->journalists());
    }

    #[Test]
    public function it_handles_comments_count(): void
    {
        $context = new EditorialContext('123');

        self::assertSame(0, $context->commentsCount());

        $context->setCommentsCount(42);

        self::assertSame(42, $context->commentsCount());
    }

    #[Test]
    public function it_handles_membership_links(): void
    {
        $context = new EditorialContext('123');

        self::assertSame([], $context->membershipLinks());

        $links = ['url1' => 'resolved1', 'url2' => 'resolved2'];
        $context->setMembershipLinks($links);

        self::assertSame($links, $context->membershipLinks());
    }

    #[Test]
    public function it_handles_multimedia_opening(): void
    {
        $context = new EditorialContext('123');

        self::assertSame([], $context->multimediaOpening());

        $opening = ['type' => 'photo', 'data' => ['id' => '456']];
        $context->setMultimediaOpening($opening);

        self::assertSame($opening, $context->multimediaOpening());
    }

    #[Test]
    public function it_handles_body_photos(): void
    {
        $context = new EditorialContext('123');

        self::assertSame([], $context->bodyPhotos());

        $photos = ['photo1' => ['url' => 'http://example.com/1.jpg']];
        $context->setBodyPhotos($photos);

        self::assertSame($photos, $context->bodyPhotos());
    }

    #[Test]
    public function it_handles_inserted_news(): void
    {
        $context = new EditorialContext('123');

        self::assertSame([], $context->insertedNews());

        $news = ['456' => ['editorial' => 'data', 'section' => 'data']];
        $context->setInsertedNews($news);

        self::assertSame($news, $context->insertedNews());
    }

    #[Test]
    public function it_handles_recommended_editorials(): void
    {
        $context = new EditorialContext('123');

        self::assertSame([], $context->recommendedEditorials());

        $editorials = ['789' => ['editorial' => 'data', 'section' => 'data']];
        $context->setRecommendedEditorials($editorials);

        self::assertSame($editorials, $context->recommendedEditorials());
    }
}
