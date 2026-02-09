<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\Enricher\InsertedNewsEnricher;
use App\Domain\Port\Gateway\EditorialGatewayInterface;
use App\Domain\Port\Gateway\JournalistGatewayInterface;
use App\Domain\Port\Gateway\SectionGatewayInterface;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(InsertedNewsEnricher::class)]
final class InsertedNewsEnricherTest extends TestCase
{
    private EditorialGatewayInterface&MockObject $editorialGateway;
    private SectionGatewayInterface&MockObject $sectionGateway;
    private JournalistGatewayInterface&MockObject $journalistGateway;
    private LoggerInterface&MockObject $logger;
    private InsertedNewsEnricher $enricher;

    protected function setUp(): void
    {
        $this->editorialGateway = $this->createMock(EditorialGatewayInterface::class);
        $this->sectionGateway = $this->createMock(SectionGatewayInterface::class);
        $this->journalistGateway = $this->createMock(JournalistGatewayInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->enricher = new InsertedNewsEnricher(
            $this->editorialGateway,
            $this->sectionGateway,
            $this->journalistGateway,
            $this->logger,
        );
    }

    #[Test]
    public function it_has_priority_55(): void
    {
        self::assertSame(55, $this->enricher->priority());
    }

    #[Test]
    public function it_does_not_support_context_without_editorial(): void
    {
        $context = new EditorialContext('123');

        self::assertFalse($this->enricher->supports($context));
    }

    #[Test]
    public function it_does_not_support_context_when_editorial_has_no_body(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('body')->willReturn(null);

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        self::assertFalse($this->enricher->supports($context));
    }

    #[Test]
    public function it_supports_context_when_editorial_has_body(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('body')->willReturn($this->createMock(Body::class));

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        self::assertTrue($this->enricher->supports($context));
    }

    #[Test]
    public function it_enriches_context_with_inserted_news_from_body_elements(): void
    {
        $elementId = $this->createMockId('inserted-editorial-1');
        $element = $this->createMock(BodyTagInsertedNews::class);
        $element->method('editorialId')->willReturn($elementId);

        $body = $this->createMock(Body::class);
        $body->method('bodyElementsOf')
            ->with(BodyTagInsertedNews::class)
            ->willReturn([$element]);

        $mainEditorial = $this->createMock(NewsBase::class);
        $mainEditorial->method('body')->willReturn($body);

        $multimediaId = $this->createMockId('multimedia-42');
        $multimedia = new class($multimediaId) {
            public function __construct(private readonly object $multimediaId)
            {
            }

            public function id(): object
            {
                return $this->multimediaId;
            }
        };

        $signatureId = $this->createMockId('alias-1');
        $signature = $this->createMock(\Ec\Editorial\Domain\Model\Signature::class);
        $signature->method('id')->willReturn($signatureId);
        $signatures = new \ArrayObject([$signature]);

        $referencedEditorial = $this->createMock(NewsBase::class);
        $referencedEditorial->method('isVisible')->willReturn(true);
        $referencedEditorial->method('sectionId')->willReturn('section-10');
        $referencedEditorial->method('signatures')->willReturn($signatures);
        $referencedEditorial->method('multimedia')->willReturn($multimedia);
        $referencedEditorial->method('metaImage')->willReturn('meta-img');

        $section = $this->createMock(Section::class);
        $journalist = $this->createMock(Journalist::class);

        $this->editorialGateway->expects(self::once())
            ->method('findById')
            ->with('inserted-editorial-1')
            ->willReturn($referencedEditorial);

        $this->sectionGateway->expects(self::once())
            ->method('findById')
            ->with('section-10')
            ->willReturn($section);

        $this->journalistGateway->expects(self::once())
            ->method('findByAliasId')
            ->with('alias-1')
            ->willReturn($journalist);

        $context = new EditorialContext('123');
        $context->setEditorial($mainEditorial);

        $this->enricher->enrich($context);

        $insertedNews = $context->insertedNews();
        self::assertArrayHasKey('inserted-editorial-1', $insertedNews);
        self::assertSame($referencedEditorial, $insertedNews['inserted-editorial-1']['editorial']);
        self::assertSame($section, $insertedNews['inserted-editorial-1']['section']);
        self::assertSame(['alias-1' => $journalist], $insertedNews['inserted-editorial-1']['signatures']);
        self::assertSame('multimedia-42', $insertedNews['inserted-editorial-1']['multimediaId']);
    }

    #[Test]
    public function it_skips_non_visible_editorials(): void
    {
        $elementId = $this->createMockId('inserted-editorial-1');
        $element = $this->createMock(BodyTagInsertedNews::class);
        $element->method('editorialId')->willReturn($elementId);

        $body = $this->createMock(Body::class);
        $body->method('bodyElementsOf')
            ->with(BodyTagInsertedNews::class)
            ->willReturn([$element]);

        $mainEditorial = $this->createMock(NewsBase::class);
        $mainEditorial->method('body')->willReturn($body);

        $referencedEditorial = $this->createMock(NewsBase::class);
        $referencedEditorial->method('isVisible')->willReturn(false);

        $this->editorialGateway->expects(self::once())
            ->method('findById')
            ->with('inserted-editorial-1')
            ->willReturn($referencedEditorial);

        $this->sectionGateway->expects(self::never())->method('findById');
        $this->journalistGateway->expects(self::never())->method('findByAliasId');

        $context = new EditorialContext('123');
        $context->setEditorial($mainEditorial);

        $this->enricher->enrich($context);

        self::assertSame([], $context->insertedNews());
    }

    #[Test]
    public function it_skips_editorials_that_fail_to_fetch(): void
    {
        $elementId = $this->createMockId('inserted-editorial-1');
        $element = $this->createMock(BodyTagInsertedNews::class);
        $element->method('editorialId')->willReturn($elementId);

        $body = $this->createMock(Body::class);
        $body->method('bodyElementsOf')
            ->with(BodyTagInsertedNews::class)
            ->willReturn([$element]);

        $mainEditorial = $this->createMock(NewsBase::class);
        $mainEditorial->method('body')->willReturn($body);

        $this->editorialGateway->expects(self::once())
            ->method('findById')
            ->with('inserted-editorial-1')
            ->willReturn(null);

        $this->sectionGateway->expects(self::never())->method('findById');
        $this->journalistGateway->expects(self::never())->method('findByAliasId');

        $context = new EditorialContext('123');
        $context->setEditorial($mainEditorial);

        $this->enricher->enrich($context);

        self::assertSame([], $context->insertedNews());
    }

    #[Test]
    public function it_handles_errors_gracefully_and_continues(): void
    {
        $elementId1 = $this->createMockId('editorial-fail');
        $element1 = $this->createMock(BodyTagInsertedNews::class);
        $element1->method('editorialId')->willReturn($elementId1);

        $elementId2 = $this->createMockId('editorial-ok');
        $element2 = $this->createMock(BodyTagInsertedNews::class);
        $element2->method('editorialId')->willReturn($elementId2);

        $body = $this->createMock(Body::class);
        $body->method('bodyElementsOf')
            ->with(BodyTagInsertedNews::class)
            ->willReturn([$element1, $element2]);

        $mainEditorial = $this->createMock(NewsBase::class);
        $mainEditorial->method('body')->willReturn($body);

        $multimediaId = $this->createMockId('mm-1');
        $multimedia = new class($multimediaId) {
            public function __construct(private readonly object $multimediaId)
            {
            }

            public function id(): object
            {
                return $this->multimediaId;
            }
        };

        $signatures = new \ArrayObject([]);

        $referencedEditorial = $this->createMock(NewsBase::class);
        $referencedEditorial->method('isVisible')->willReturn(true);
        $referencedEditorial->method('sectionId')->willReturn('section-1');
        $referencedEditorial->method('signatures')->willReturn($signatures);
        $referencedEditorial->method('multimedia')->willReturn($multimedia);

        $section = $this->createMock(Section::class);

        $this->editorialGateway->expects(self::exactly(2))
            ->method('findById')
            ->willReturnCallback(function (string $id) use ($referencedEditorial) {
                if ('editorial-fail' === $id) {
                    throw new \RuntimeException('Connection timeout');
                }

                return $referencedEditorial;
            });

        $this->sectionGateway->expects(self::once())
            ->method('findById')
            ->with('section-1')
            ->willReturn($section);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Failed to fetch inserted news', self::callback(
                fn (array $logContext) => 'editorial-fail' === $logContext['editorialId']
                    && 'Connection timeout' === $logContext['error']
            ));

        $context = new EditorialContext('123');
        $context->setEditorial($mainEditorial);

        $this->enricher->enrich($context);

        $insertedNews = $context->insertedNews();
        self::assertArrayNotHasKey('editorial-fail', $insertedNews);
        self::assertArrayHasKey('editorial-ok', $insertedNews);
        self::assertSame($referencedEditorial, $insertedNews['editorial-ok']['editorial']);
    }

    #[Test]
    public function it_stores_empty_array_when_no_inserted_news_elements(): void
    {
        $body = $this->createMock(Body::class);
        $body->method('bodyElementsOf')
            ->with(BodyTagInsertedNews::class)
            ->willReturn([]);

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('body')->willReturn($body);

        $this->editorialGateway->expects(self::never())->method('findById');
        $this->sectionGateway->expects(self::never())->method('findById');
        $this->journalistGateway->expects(self::never())->method('findByAliasId');

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        self::assertSame([], $context->insertedNews());
    }

    #[Test]
    public function it_uses_meta_image_when_multimedia_id_is_empty(): void
    {
        $elementId = $this->createMockId('inserted-editorial-1');
        $element = $this->createMock(BodyTagInsertedNews::class);
        $element->method('editorialId')->willReturn($elementId);

        $body = $this->createMock(Body::class);
        $body->method('bodyElementsOf')
            ->with(BodyTagInsertedNews::class)
            ->willReturn([$element]);

        $mainEditorial = $this->createMock(NewsBase::class);
        $mainEditorial->method('body')->willReturn($body);

        $emptyMultimediaId = $this->createMockId('');
        $multimedia = new class($emptyMultimediaId) {
            public function __construct(private readonly object $emptyMultimediaId)
            {
            }

            public function id(): object
            {
                return $this->emptyMultimediaId;
            }
        };

        $signatures = new \ArrayObject([]);

        $referencedEditorial = $this->createMock(NewsBase::class);
        $referencedEditorial->method('isVisible')->willReturn(true);
        $referencedEditorial->method('sectionId')->willReturn('section-5');
        $referencedEditorial->method('signatures')->willReturn($signatures);
        $referencedEditorial->method('multimedia')->willReturn($multimedia);
        $referencedEditorial->method('metaImage')->willReturn('meta-image-fallback');

        $section = $this->createMock(Section::class);

        $this->editorialGateway->expects(self::once())
            ->method('findById')
            ->with('inserted-editorial-1')
            ->willReturn($referencedEditorial);

        $this->sectionGateway->expects(self::once())
            ->method('findById')
            ->with('section-5')
            ->willReturn($section);

        $context = new EditorialContext('123');
        $context->setEditorial($mainEditorial);

        $this->enricher->enrich($context);

        $insertedNews = $context->insertedNews();
        self::assertSame('meta-image-fallback', $insertedNews['inserted-editorial-1']['multimediaId']);
    }

    /**
     * Creates a value object stub with an id() method returning the given string.
     *
     * Used to simulate the value object chain: $element->editorialId()->id().
     */
    private function createMockId(string $id): object
    {
        return new class($id) {
            public function __construct(private readonly string $value)
            {
            }

            public function id(): string
            {
                return $this->value;
            }
        };
    }
}
