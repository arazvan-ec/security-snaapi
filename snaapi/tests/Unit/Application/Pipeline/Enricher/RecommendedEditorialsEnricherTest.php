<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\Enricher\RecommendedEditorialsEnricher;
use App\Domain\Port\Gateway\EditorialGatewayInterface;
use App\Domain\Port\Gateway\JournalistGatewayInterface;
use App\Domain\Port\Gateway\SectionGatewayInterface;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(RecommendedEditorialsEnricher::class)]
final class RecommendedEditorialsEnricherTest extends TestCase
{
    private EditorialGatewayInterface&MockObject $editorialGateway;
    private SectionGatewayInterface&MockObject $sectionGateway;
    private JournalistGatewayInterface&MockObject $journalistGateway;
    private LoggerInterface&MockObject $logger;
    private RecommendedEditorialsEnricher $enricher;

    protected function setUp(): void
    {
        $this->editorialGateway = $this->createMock(EditorialGatewayInterface::class);
        $this->sectionGateway = $this->createMock(SectionGatewayInterface::class);
        $this->journalistGateway = $this->createMock(JournalistGatewayInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->enricher = new RecommendedEditorialsEnricher(
            $this->editorialGateway,
            $this->sectionGateway,
            $this->journalistGateway,
            $this->logger,
        );
    }

    public function testPriorityReturns25(): void
    {
        self::assertSame(25, $this->enricher->priority());
    }

    public function testSupportsReturnsFalseWhenNoEditorial(): void
    {
        $context = new EditorialContext('123');

        self::assertFalse($this->enricher->supports($context));
    }

    public function testSupportsReturnsTrueWhenEditorialExists(): void
    {
        $context = new EditorialContext('123');
        $context->setEditorial($this->createMock(NewsBase::class));

        self::assertTrue($this->enricher->supports($context));
    }

    public function testEnrichPopulatesRecommendedEditorials(): void
    {
        $recEditorial = $this->createMock(NewsBase::class);
        $recEditorial->method('isVisible')->willReturn(true);
        $recEditorial->method('sectionId')->willReturn('section-1');
        $recEditorial->method('signatures')->willReturn(new \ArrayObject([]));
        $recEditorial->method('multimedia')->willReturn($this->createMultimediaRef('multimedia-1'));
        $recEditorial->method('metaImage')->willReturn('meta-image-1');

        $section = $this->createMock(Section::class);

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('recommendedEditorials')->willReturn(
            $this->createRecommendedEditorialIds(['rec-1']),
        );

        $this->editorialGateway->expects(self::once())
            ->method('findById')
            ->with('rec-1')
            ->willReturn($recEditorial);

        $this->sectionGateway->expects(self::once())
            ->method('findById')
            ->with('section-1')
            ->willReturn($section);

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        $result = $context->recommendedEditorials();

        self::assertArrayHasKey('rec-1', $result);
        self::assertSame($recEditorial, $result['rec-1']['editorial']);
        self::assertSame($section, $result['rec-1']['section']);
        self::assertSame([], $result['rec-1']['signatures']);
        self::assertSame('multimedia-1', $result['rec-1']['multimediaId']);
    }

    public function testEnrichSkipsNonVisibleRecommendedEditorials(): void
    {
        $recEditorial = $this->createMock(NewsBase::class);
        $recEditorial->method('isVisible')->willReturn(false);

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('recommendedEditorials')->willReturn(
            $this->createRecommendedEditorialIds(['rec-1']),
        );

        $this->editorialGateway->expects(self::once())
            ->method('findById')
            ->with('rec-1')
            ->willReturn($recEditorial);

        $this->sectionGateway->expects(self::never())->method('findById');

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        self::assertSame([], $context->recommendedEditorials());
    }

    public function testEnrichSkipsRecommendedEditorialsThatFailToFetch(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('recommendedEditorials')->willReturn(
            $this->createRecommendedEditorialIds(['rec-1']),
        );

        $this->editorialGateway->expects(self::once())
            ->method('findById')
            ->with('rec-1')
            ->willReturn(null);

        $this->sectionGateway->expects(self::never())->method('findById');

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        self::assertSame([], $context->recommendedEditorials());
    }

    public function testEnrichHandlesErrorsGracefully(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('recommendedEditorials')->willReturn(
            $this->createRecommendedEditorialIds(['rec-1', 'rec-2']),
        );

        $recEditorial2 = $this->createMock(NewsBase::class);
        $recEditorial2->method('isVisible')->willReturn(true);
        $recEditorial2->method('sectionId')->willReturn('section-2');
        $recEditorial2->method('signatures')->willReturn(new \ArrayObject([]));
        $recEditorial2->method('multimedia')->willReturn($this->createMultimediaRef('multimedia-2'));
        $recEditorial2->method('metaImage')->willReturn('meta-image-2');

        $section = $this->createMock(Section::class);

        $this->editorialGateway->expects(self::exactly(2))
            ->method('findById')
            ->willReturnCallback(function (string $id) use ($recEditorial2) {
                if ('rec-1' === $id) {
                    throw new \RuntimeException('Connection timeout');
                }

                return $recEditorial2;
            });

        $this->sectionGateway->expects(self::once())
            ->method('findById')
            ->with('section-2')
            ->willReturn($section);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Failed to fetch recommended editorial', self::callback(
                fn (array $logContext) => 'rec-1' === $logContext['editorialId']
                    && 'Connection timeout' === $logContext['error'],
            ));

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        $result = $context->recommendedEditorials();

        self::assertArrayNotHasKey('rec-1', $result);
        self::assertArrayHasKey('rec-2', $result);
        self::assertSame($recEditorial2, $result['rec-2']['editorial']);
    }

    public function testEnrichStoresEmptyArrayWhenNoRecommendedEditorialIds(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('recommendedEditorials')->willReturn(
            $this->createRecommendedEditorialIds([]),
        );

        $this->editorialGateway->expects(self::never())->method('findById');

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        self::assertSame([], $context->recommendedEditorials());
    }

    public function testEnrichFetchesJournalistsForEachSignature(): void
    {
        $signature1 = $this->createSignatureStub('alias-1');
        $signature2 = $this->createSignatureStub('alias-2');

        $recEditorial = $this->createMock(NewsBase::class);
        $recEditorial->method('isVisible')->willReturn(true);
        $recEditorial->method('sectionId')->willReturn('section-1');
        $recEditorial->method('signatures')->willReturn(new \ArrayObject([$signature1, $signature2]));
        $recEditorial->method('multimedia')->willReturn($this->createMultimediaRef('multimedia-1'));
        $recEditorial->method('metaImage')->willReturn('meta-image-1');

        $section = $this->createMock(Section::class);
        $journalist1 = $this->createMock(Journalist::class);
        $journalist2 = $this->createMock(Journalist::class);

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('recommendedEditorials')->willReturn(
            $this->createRecommendedEditorialIds(['rec-1']),
        );

        $this->editorialGateway->expects(self::once())
            ->method('findById')
            ->with('rec-1')
            ->willReturn($recEditorial);

        $this->sectionGateway->expects(self::once())
            ->method('findById')
            ->with('section-1')
            ->willReturn($section);

        $this->journalistGateway->expects(self::exactly(2))
            ->method('findByAliasId')
            ->willReturnMap([
                ['alias-1', $journalist1],
                ['alias-2', $journalist2],
            ]);

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        $result = $context->recommendedEditorials();

        self::assertArrayHasKey('rec-1', $result);
        self::assertSame(['alias-1' => $journalist1, 'alias-2' => $journalist2], $result['rec-1']['signatures']);
    }

    public function testEnrichUsesMetaImageWhenMultimediaIdIsEmpty(): void
    {
        $recEditorial = $this->createMock(NewsBase::class);
        $recEditorial->method('isVisible')->willReturn(true);
        $recEditorial->method('sectionId')->willReturn('section-1');
        $recEditorial->method('signatures')->willReturn(new \ArrayObject([]));
        $recEditorial->method('multimedia')->willReturn($this->createMultimediaRef(''));
        $recEditorial->method('metaImage')->willReturn('meta-image-fallback');

        $section = $this->createMock(Section::class);

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('recommendedEditorials')->willReturn(
            $this->createRecommendedEditorialIds(['rec-1']),
        );

        $this->editorialGateway->expects(self::once())
            ->method('findById')
            ->with('rec-1')
            ->willReturn($recEditorial);

        $this->sectionGateway->expects(self::once())
            ->method('findById')
            ->with('section-1')
            ->willReturn($section);

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        $result = $context->recommendedEditorials();

        self::assertSame('meta-image-fallback', $result['rec-1']['multimediaId']);
    }

    /**
     * Creates a stub for the recommended editorials collection with editorialIds().
     *
     * @param list<string> $ids
     */
    private function createRecommendedEditorialIds(array $ids): object
    {
        $editorialIds = array_map(
            fn (string $id) => $this->createEditorialIdStub($id),
            $ids,
        );

        return new class($editorialIds) {
            /** @param list<object> $ids */
            public function __construct(private readonly array $ids)
            {
            }

            /** @return list<object> */
            public function editorialIds(): array
            {
                return $this->ids;
            }
        };
    }

    /**
     * Creates a value object stub with an id() method returning the given string.
     */
    private function createEditorialIdStub(string $id): object
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

    /**
     * Creates a signature stub with an id()->id() chain returning the given alias ID.
     */
    private function createSignatureStub(string $aliasId): object
    {
        $idObject = $this->createEditorialIdStub($aliasId);

        return new class($idObject) {
            public function __construct(private readonly object $idObject)
            {
            }

            public function id(): object
            {
                return $this->idObject;
            }
        };
    }

    /**
     * Creates a multimedia reference stub with id()->id() chain returning the given ID.
     */
    private function createMultimediaRef(string $id): object
    {
        $idObject = $this->createEditorialIdStub($id);

        return new class($idObject) {
            public function __construct(private readonly object $idObject)
            {
            }

            public function id(): object
            {
                return $this->idObject;
            }
        };
    }
}
