<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orchestrator\Chain;

use App\Application\DTO\Response\BodyElementResponse;
use App\Application\DTO\Response\BodyResponse;
use App\Application\DTO\Response\EditorialResponse;
use App\Application\DTO\Response\MultimediaResponse;
use App\Application\DTO\Response\SectionResponse;
use App\Application\DTO\Response\SignatureResponse;
use App\Application\DTO\Response\TagResponse;
use App\Application\DTO\Response\TitlesResponse;
use App\Application\Factory\Response\EditorialResponseFactory;
use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnricherInterface;
use App\Application\Pipeline\Enricher\BodyPhotosEnricher;
use App\Application\Pipeline\Enricher\CommentsEnricher;
use App\Application\Pipeline\Enricher\EditorialEnricher;
use App\Application\Pipeline\Enricher\InsertedNewsEnricher;
use App\Application\Pipeline\Enricher\JournalistsEnricher;
use App\Application\Pipeline\Enricher\MembershipEnricher;
use App\Application\Pipeline\Enricher\MultimediaEnricher;
use App\Application\Pipeline\Enricher\OpeningMultimediaEnricher;
use App\Application\Pipeline\Enricher\RecommendedEditorialsEnricher;
use App\Application\Pipeline\Enricher\SectionEnricher;
use App\Application\Pipeline\Enricher\TagsEnricher;
use App\Application\Pipeline\EnrichmentPipeline;
use App\Application\Strategy\Visibility\AllowAllStrategy;
use App\Application\Strategy\Visibility\PublishedOnlyStrategy;
use App\Exception\EditorialNotPublishedYetException;
use App\Orchestrator\Chain\PipelineEditorialOrchestrator;
use Ec\Editorial\Domain\Model\NewsBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(PipelineEditorialOrchestrator::class)]
#[CoversClass(EnrichmentPipeline::class)]
#[CoversClass(EditorialResponse::class)]
final class PipelineJsonParityTest extends TestCase
{
    private const EDITORIAL_ID = '12345';
    private const EDITORIAL_URL = 'https://www.example.com/editorial/12345/test-editorial';
    private const PUBLICATION_DATE = '2025-03-15 10:30:00';
    private const UPDATED_ON = '2025-03-15 14:00:00';

    private EnrichmentPipeline&MockObject $pipeline;
    private EditorialResponseFactory&MockObject $responseFactory;

    protected function setUp(): void
    {
        $this->pipeline = $this->createMock(EnrichmentPipeline::class);
        $this->responseFactory = $this->createMock(EditorialResponseFactory::class);
    }

    protected function tearDown(): void
    {
        unset($this->pipeline, $this->responseFactory);
    }

    #[Test]
    public function testPipelineProducesCompleteResponse(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('isVisible')->willReturn(true);

        $editorialResponse = $this->buildFullEditorialResponse();

        $this->pipeline->method('process')
            ->willReturnCallback(function (EditorialContext $ctx) use ($editorial): EditorialContext {
                $ctx->setEditorial($editorial);

                return $ctx;
            });

        $this->responseFactory->method('create')->willReturn($editorialResponse);

        $visibilityStrategy = new AllowAllStrategy();

        $orchestrator = new PipelineEditorialOrchestrator(
            $this->pipeline,
            $visibilityStrategy,
            $this->responseFactory,
            'editorial',
        );

        $request = Request::create('/editorials/' . self::EDITORIAL_ID);
        $request->attributes->set('id', self::EDITORIAL_ID);

        $result = $orchestrator->execute($request);

        // Verify all top-level keys expected by legacy orchestrators are present
        $expectedKeys = [
            'id',
            'url',
            'titles',
            'lead',
            'publicationDate',
            'updatedOn',
            'type',
            'indexable',
            'deleted',
            'published',
            'closingModeId',
            'commentable',
            'isBrand',
            'isAmazonOnsite',
            'contentType',
            'canonicalEditorialId',
            'urlDate',
            'countWords',
            'body',
            'signatures',
            'section',
            'tags',
            'countComments',
            'multimedia',
            'standfirst',
            'recommendedEditorials',
        ];

        foreach ($expectedKeys as $key) {
            self::assertArrayHasKey($key, $result, sprintf('Missing key "%s" in pipeline response', $key));
        }

        // Verify scalar values
        self::assertSame(self::EDITORIAL_ID, $result['id']);
        self::assertSame(self::EDITORIAL_URL, $result['url']);
        self::assertSame('This is the editorial lead text.', $result['lead']);
        self::assertSame(self::PUBLICATION_DATE, $result['publicationDate']);
        self::assertSame(self::UPDATED_ON, $result['updatedOn']);
        self::assertTrue($result['indexable']);
        self::assertFalse($result['deleted']);
        self::assertTrue($result['published']);
        self::assertSame('0', $result['closingModeId']);
        self::assertTrue($result['commentable']);
        self::assertFalse($result['isBrand']);
        self::assertFalse($result['isAmazonOnsite']);
        self::assertSame('editorial', $result['contentType']);
        self::assertSame('11111', $result['canonicalEditorialId']);
        self::assertSame('2025-03-15 00:00:00', $result['urlDate']);
        self::assertSame(850, $result['countWords']);
        self::assertSame(42, $result['countComments']);

        // Verify nested type object
        self::assertIsArray($result['type']);
        self::assertSame('1', $result['type']['id']);
        self::assertSame('news', $result['type']['name']);

        // Verify titles structure
        $titles = $result['titles'];
        self::assertInstanceOf(\JsonSerializable::class, $titles);

        // Verify body structure
        $body = $result['body'];
        self::assertInstanceOf(\JsonSerializable::class, $body);

        // Verify signatures
        self::assertIsArray($result['signatures']);
        self::assertNotEmpty($result['signatures']);
        self::assertInstanceOf(SignatureResponse::class, $result['signatures'][0]);

        // Verify section
        self::assertInstanceOf(SectionResponse::class, $result['section']);

        // Verify tags
        self::assertIsArray($result['tags']);
        self::assertNotEmpty($result['tags']);
        self::assertInstanceOf(TagResponse::class, $result['tags'][0]);

        // Verify multimedia
        self::assertInstanceOf(MultimediaResponse::class, $result['multimedia']);

        // Verify standfirst
        self::assertIsArray($result['standfirst']);
        self::assertArrayHasKey('title', $result['standfirst']);
        self::assertArrayHasKey('items', $result['standfirst']);

        // Verify recommendedEditorials
        self::assertIsArray($result['recommendedEditorials']);
    }

    #[Test]
    public function testEditorialPipelineWithPublishedOnlyStrategy(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('isVisible')->willReturn(false);

        $this->pipeline->method('process')
            ->willReturnCallback(function (EditorialContext $ctx) use ($editorial): EditorialContext {
                $ctx->setEditorial($editorial);

                return $ctx;
            });

        $visibilityStrategy = new PublishedOnlyStrategy();

        $orchestrator = new PipelineEditorialOrchestrator(
            $this->pipeline,
            $visibilityStrategy,
            $this->responseFactory,
            'editorial',
        );

        $request = Request::create('/editorials/' . self::EDITORIAL_ID);
        $request->attributes->set('id', self::EDITORIAL_ID);

        $this->expectException(EditorialNotPublishedYetException::class);
        $this->expectExceptionMessage('Editorial not published');
        $this->expectExceptionCode(404);

        $orchestrator->execute($request);
    }

    #[Test]
    public function testEditorialPipelineWithAllowAllStrategy(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('isVisible')->willReturn(false);

        $editorialResponse = $this->buildFullEditorialResponse();

        $this->pipeline->method('process')
            ->willReturnCallback(function (EditorialContext $ctx) use ($editorial): EditorialContext {
                $ctx->setEditorial($editorial);

                return $ctx;
            });

        $this->responseFactory->method('create')->willReturn($editorialResponse);

        $visibilityStrategy = new AllowAllStrategy();

        $orchestrator = new PipelineEditorialOrchestrator(
            $this->pipeline,
            $visibilityStrategy,
            $this->responseFactory,
            'editorial',
        );

        $request = Request::create('/editorials/' . self::EDITORIAL_ID);
        $request->attributes->set('id', self::EDITORIAL_ID);

        $result = $orchestrator->execute($request);

        self::assertIsArray($result);
        self::assertArrayHasKey('id', $result);
        self::assertSame(self::EDITORIAL_ID, $result['id']);
    }

    #[Test]
    public function testPipelineEnrichersRunInPriorityOrder(): void
    {
        $executionOrder = [];

        $enricherConfigs = [
            ['class' => 'EditorialEnricher', 'priority' => 100],
            ['class' => 'SectionEnricher', 'priority' => 90],
            ['class' => 'MultimediaEnricher', 'priority' => 80],
            ['class' => 'OpeningMultimediaEnricher', 'priority' => 75],
            ['class' => 'TagsEnricher', 'priority' => 70],
            ['class' => 'JournalistsEnricher', 'priority' => 60],
            ['class' => 'InsertedNewsEnricher', 'priority' => 55],
            ['class' => 'MembershipEnricher', 'priority' => 50],
            ['class' => 'CommentsEnricher', 'priority' => 40],
            ['class' => 'BodyPhotosEnricher', 'priority' => 35],
            ['class' => 'RecommendedEditorialsEnricher', 'priority' => 25],
        ];

        // Shuffle to prove sorting works regardless of input order
        $shuffled = $enricherConfigs;
        shuffle($shuffled);

        $enrichers = [];

        foreach ($shuffled as $config) {
            $enricher = $this->createMock(EnricherInterface::class);
            $enricher->method('priority')->willReturn($config['priority']);
            $enricher->method('supports')->willReturn(true);
            $enricher->method('enrich')
                ->willReturnCallback(function () use (&$executionOrder, $config): void {
                    $executionOrder[] = $config['priority'];
                });
            $enrichers[] = $enricher;
        }

        $pipeline = new EnrichmentPipeline($enrichers, new NullLogger());
        $context = new EditorialContext(self::EDITORIAL_ID);

        $pipeline->process($context);

        $expectedOrder = [100, 90, 80, 75, 70, 60, 55, 50, 40, 35, 25];
        self::assertSame($expectedOrder, $executionOrder, 'Enrichers did not execute in descending priority order');
    }

    #[Test]
    public function testAllEnrichersRegistered(): void
    {
        $expectedEnrichers = [
            EditorialEnricher::class => 100,
            SectionEnricher::class => 90,
            MultimediaEnricher::class => 80,
            OpeningMultimediaEnricher::class => 75,
            TagsEnricher::class => 70,
            JournalistsEnricher::class => 60,
            InsertedNewsEnricher::class => 55,
            MembershipEnricher::class => 50,
            CommentsEnricher::class => 40,
            BodyPhotosEnricher::class => 35,
            RecommendedEditorialsEnricher::class => 25,
        ];

        foreach ($expectedEnrichers as $enricherClass => $expectedPriority) {
            self::assertTrue(
                class_exists($enricherClass),
                sprintf('Enricher class %s does not exist', $enricherClass),
            );

            $reflection = new \ReflectionClass($enricherClass);
            self::assertTrue(
                $reflection->implementsInterface(EnricherInterface::class),
                sprintf('Enricher %s does not implement EnricherInterface', $enricherClass),
            );

            // Verify priority via reflection on the method return type
            $priorityMethod = $reflection->getMethod('priority');
            self::assertTrue(
                $priorityMethod->isPublic(),
                sprintf('Enricher %s::priority() must be public', $enricherClass),
            );

            // Verify supports method exists
            $supportsMethod = $reflection->getMethod('supports');
            self::assertTrue(
                $supportsMethod->isPublic(),
                sprintf('Enricher %s::supports() must be public', $enricherClass),
            );

            // Verify enrich method exists
            $enrichMethod = $reflection->getMethod('enrich');
            self::assertTrue(
                $enrichMethod->isPublic(),
                sprintf('Enricher %s::enrich() must be public', $enricherClass),
            );
        }

        self::assertCount(
            11,
            $expectedEnrichers,
            'Expected exactly 11 enrichers in the pipeline',
        );
    }

    #[Test]
    public function testJsonSerializeOutputMatchesLegacyKeys(): void
    {
        $response = $this->buildFullEditorialResponse();
        $serialized = $response->jsonSerialize();

        // The legacy orchestrator produces a flat array with these exact keys.
        // The type key is a nested object { id, name } rather than separate typeId/typeName.
        $legacyKeys = [
            'id',
            'url',
            'titles',
            'lead',
            'publicationDate',
            'updatedOn',
            'type',
            'indexable',
            'deleted',
            'published',
            'closingModeId',
            'commentable',
            'isBrand',
            'isAmazonOnsite',
            'contentType',
            'canonicalEditorialId',
            'urlDate',
            'countWords',
            'body',
            'multimedia',
            'signatures',
            'section',
            'tags',
            'countComments',
            'standfirst',
            'recommendedEditorials',
        ];

        foreach ($legacyKeys as $key) {
            self::assertArrayHasKey(
                $key,
                $serialized,
                sprintf('Legacy key "%s" missing from EditorialResponse::jsonSerialize()', $key),
            );
        }

        // Verify type is a nested array with id and name
        self::assertIsArray($serialized['type']);
        self::assertArrayHasKey('id', $serialized['type']);
        self::assertArrayHasKey('name', $serialized['type']);
    }

    #[Test]
    public function testJsonSerializeFiltersNullValues(): void
    {
        $response = new EditorialResponse(
            id: self::EDITORIAL_ID,
            url: self::EDITORIAL_URL,
            titles: new TitlesResponse(preTitle: null, title: 'Test Title', urlTitle: 'test-title'),
            lead: 'Lead text',
            publicationDate: self::PUBLICATION_DATE,
            updatedOn: null,
            endOn: null,
            typeId: '1',
            typeName: 'news',
            indexable: true,
            deleted: false,
            published: true,
            closingModeId: null,
            commentable: true,
            isBrand: false,
            isAmazonOnsite: false,
            contentType: null,
            canonicalEditorialId: null,
            urlDate: null,
            countWords: 100,
            body: new BodyResponse(type: 'body', elements: []),
            signatures: [],
            section: null,
            tags: [],
            countComments: 0,
        );

        $serialized = $response->jsonSerialize();

        // Null values should be filtered out by array_filter
        self::assertArrayNotHasKey('updatedOn', $serialized);
        self::assertArrayNotHasKey('endOn', $serialized);
        self::assertArrayNotHasKey('closingModeId', $serialized);
        self::assertArrayNotHasKey('contentType', $serialized);
        self::assertArrayNotHasKey('canonicalEditorialId', $serialized);
        self::assertArrayNotHasKey('urlDate', $serialized);
        self::assertArrayNotHasKey('section', $serialized);
        self::assertArrayNotHasKey('multimedia', $serialized);
        self::assertArrayNotHasKey('standfirst', $serialized);
        self::assertArrayNotHasKey('recommendedEditorials', $serialized);

        // Non-null values should remain
        self::assertArrayHasKey('id', $serialized);
        self::assertArrayHasKey('url', $serialized);
        self::assertArrayHasKey('titles', $serialized);
        self::assertArrayHasKey('lead', $serialized);
        self::assertArrayHasKey('publicationDate', $serialized);
        self::assertArrayHasKey('body', $serialized);
    }

    #[Test]
    public function testPipelineContinuesOnEnricherFailure(): void
    {
        $executionLog = [];

        $failingEnricher = $this->createMock(EnricherInterface::class);
        $failingEnricher->method('priority')->willReturn(90);
        $failingEnricher->method('supports')->willReturn(true);
        $failingEnricher->method('enrich')
            ->willReturnCallback(function () use (&$executionLog): void {
                $executionLog[] = 'failing-90';
                throw new \RuntimeException('Enricher failed intentionally');
            });

        $successEnricher = $this->createMock(EnricherInterface::class);
        $successEnricher->method('priority')->willReturn(80);
        $successEnricher->method('supports')->willReturn(true);
        $successEnricher->method('enrich')
            ->willReturnCallback(function () use (&$executionLog): void {
                $executionLog[] = 'success-80';
            });

        $pipeline = new EnrichmentPipeline([$failingEnricher, $successEnricher], new NullLogger());
        $context = new EditorialContext(self::EDITORIAL_ID);

        $result = $pipeline->process($context);

        self::assertSame(['failing-90', 'success-80'], $executionLog);
        self::assertSame($context, $result);
    }

    #[Test]
    public function testPipelineSkipsUnsupportedEnrichers(): void
    {
        $executionLog = [];

        $supportedEnricher = $this->createMock(EnricherInterface::class);
        $supportedEnricher->method('priority')->willReturn(100);
        $supportedEnricher->method('supports')->willReturn(true);
        $supportedEnricher->method('enrich')
            ->willReturnCallback(function () use (&$executionLog): void {
                $executionLog[] = 'supported-100';
            });

        $unsupportedEnricher = $this->createMock(EnricherInterface::class);
        $unsupportedEnricher->method('priority')->willReturn(90);
        $unsupportedEnricher->method('supports')->willReturn(false);
        $unsupportedEnricher->expects(self::never())->method('enrich');

        $pipeline = new EnrichmentPipeline([$supportedEnricher, $unsupportedEnricher], new NullLogger());
        $context = new EditorialContext(self::EDITORIAL_ID);

        $pipeline->process($context);

        self::assertSame(['supported-100'], $executionLog);
    }

    #[Test]
    public function testResponseSignaturesSerializeCorrectly(): void
    {
        $signature = new SignatureResponse(
            aliasId: 'alias-001',
            name: 'Jane Reporter',
            description: 'Senior correspondent',
            url: 'https://www.example.com/journalists/jane-reporter',
            photoUrl: 'https://images.example.com/journalists/jane.jpg',
            twitter: '@janereporter',
        );

        $serialized = $signature->jsonSerialize();

        self::assertSame('alias-001', $serialized['aliasId']);
        self::assertSame('Jane Reporter', $serialized['name']);
        self::assertSame('Senior correspondent', $serialized['description']);
        self::assertSame('https://www.example.com/journalists/jane-reporter', $serialized['url']);
        self::assertSame('https://images.example.com/journalists/jane.jpg', $serialized['photoUrl']);
        self::assertSame('@janereporter', $serialized['twitter']);
    }

    #[Test]
    public function testResponseTagsSerializeCorrectly(): void
    {
        $tag = new TagResponse(
            id: 'tag-100',
            name: 'Technology',
            url: 'https://www.example.com/tags/technology',
        );

        $serialized = $tag->jsonSerialize();

        self::assertSame('tag-100', $serialized['id']);
        self::assertSame('Technology', $serialized['name']);
        self::assertSame('https://www.example.com/tags/technology', $serialized['url']);
    }

    #[Test]
    public function testResponseSectionSerializesCorrectly(): void
    {
        $section = new SectionResponse(
            id: 'sec-200',
            name: 'World News',
            url: 'https://www.example.com/sections/world-news',
            siteId: 'site-1',
        );

        $serialized = $section->jsonSerialize();

        self::assertSame('sec-200', $serialized['id']);
        self::assertSame('World News', $serialized['name']);
        self::assertSame('https://www.example.com/sections/world-news', $serialized['url']);
        self::assertSame('site-1', $serialized['siteId']);
    }

    #[Test]
    public function testResponseMultimediaSerializesCorrectly(): void
    {
        $multimedia = new MultimediaResponse(
            type: 'photo',
            id: 'mm-300',
            url: 'https://images.example.com/photos/mm-300.jpg',
            caption: 'A press conference photo',
            credit: 'Reuters',
            metadata: ['width' => 1920, 'height' => 1080],
        );

        $serialized = $multimedia->jsonSerialize();

        self::assertSame('photo', $serialized['type']);
        self::assertSame('mm-300', $serialized['id']);
        self::assertSame('https://images.example.com/photos/mm-300.jpg', $serialized['url']);
        self::assertSame('A press conference photo', $serialized['caption']);
        self::assertSame('Reuters', $serialized['credit']);
        self::assertSame(['width' => 1920, 'height' => 1080], $serialized['metadata']);
    }

    #[Test]
    public function testResponseBodyElementsSerializeCorrectly(): void
    {
        $paragraph = new BodyElementResponse(
            type: 'paragraph',
            content: '<p>This is a paragraph with <strong>bold</strong> text.</p>',
        );

        $subhead = new BodyElementResponse(
            type: 'subhead',
            content: 'Section heading',
            level: 2,
        );

        $image = new BodyElementResponse(
            type: 'image',
            imageUrl: 'https://images.example.com/body/img-001.jpg',
            caption: 'An inline image',
            credit: 'AP',
        );

        $body = new BodyResponse(
            type: 'body',
            elements: [$paragraph, $subhead, $image],
        );

        $serialized = $body->jsonSerialize();

        self::assertSame('body', $serialized['type']);
        self::assertCount(3, $serialized['elements']);
        self::assertInstanceOf(BodyElementResponse::class, $serialized['elements'][0]);
        self::assertInstanceOf(BodyElementResponse::class, $serialized['elements'][1]);
        self::assertInstanceOf(BodyElementResponse::class, $serialized['elements'][2]);
    }

    /**
     * @param class-string<EnricherInterface> $enricherClass
     */
    #[Test]
    #[DataProvider('enricherPriorityProvider')]
    public function testEnricherPriorityValues(string $enricherClass, int $expectedPriority): void
    {
        $reflection = new \ReflectionClass($enricherClass);
        $constructor = $reflection->getConstructor();

        self::assertNotNull($constructor, sprintf('%s must have a constructor', $enricherClass));

        // Build mock constructor arguments
        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->createMock($type->getName());
            }
        }

        /** @var EnricherInterface $enricher */
        $enricher = $reflection->newInstanceArgs($args);

        self::assertSame(
            $expectedPriority,
            $enricher->priority(),
            sprintf('%s should have priority %d', $enricherClass, $expectedPriority),
        );
    }

    /**
     * @return iterable<string, array{class-string<EnricherInterface>, int}>
     */
    public static function enricherPriorityProvider(): iterable
    {
        yield 'EditorialEnricher' => [EditorialEnricher::class, 100];
        yield 'SectionEnricher' => [SectionEnricher::class, 90];
        yield 'MultimediaEnricher' => [MultimediaEnricher::class, 80];
        yield 'OpeningMultimediaEnricher' => [OpeningMultimediaEnricher::class, 75];
        yield 'TagsEnricher' => [TagsEnricher::class, 70];
        yield 'JournalistsEnricher' => [JournalistsEnricher::class, 60];
        yield 'InsertedNewsEnricher' => [InsertedNewsEnricher::class, 55];
        yield 'MembershipEnricher' => [MembershipEnricher::class, 50];
        yield 'CommentsEnricher' => [CommentsEnricher::class, 40];
        yield 'BodyPhotosEnricher' => [BodyPhotosEnricher::class, 35];
        yield 'RecommendedEditorialsEnricher' => [RecommendedEditorialsEnricher::class, 25];
    }

    #[Test]
    public function testFullPipelineRoundtripProducesValidJson(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('isVisible')->willReturn(true);

        $editorialResponse = $this->buildFullEditorialResponse();

        $this->pipeline->method('process')
            ->willReturnCallback(function (EditorialContext $ctx) use ($editorial): EditorialContext {
                $ctx->setEditorial($editorial);

                return $ctx;
            });

        $this->responseFactory->method('create')->willReturn($editorialResponse);

        $visibilityStrategy = new AllowAllStrategy();

        $orchestrator = new PipelineEditorialOrchestrator(
            $this->pipeline,
            $visibilityStrategy,
            $this->responseFactory,
            'editorial',
        );

        $request = Request::create('/editorials/' . self::EDITORIAL_ID);
        $request->attributes->set('id', self::EDITORIAL_ID);

        $result = $orchestrator->execute($request);

        // Encode to JSON and back to verify the full structure is JSON-safe
        $json = json_encode($result, \JSON_THROW_ON_ERROR);
        self::assertIsString($json);
        self::assertNotEmpty($json);

        $decoded = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        self::assertSame(self::EDITORIAL_ID, $decoded['id']);
        self::assertSame(self::EDITORIAL_URL, $decoded['url']);
        self::assertSame('news', $decoded['type']['name']);

        // Verify nested objects survive JSON round-trip
        self::assertIsArray($decoded['titles']);
        self::assertSame('Test Editorial Title', $decoded['titles']['title']);

        self::assertIsArray($decoded['body']);
        self::assertSame('body', $decoded['body']['type']);
        self::assertCount(2, $decoded['body']['elements']);

        self::assertIsArray($decoded['section']);
        self::assertSame('sec-100', $decoded['section']['id']);

        self::assertCount(1, $decoded['signatures']);
        self::assertSame('alias-001', $decoded['signatures'][0]['aliasId']);

        self::assertCount(2, $decoded['tags']);
        self::assertSame('tag-001', $decoded['tags'][0]['id']);

        self::assertIsArray($decoded['multimedia']);
        self::assertSame('photo', $decoded['multimedia']['type']);
    }

    #[Test]
    public function testPipelineOrchestratorCanOrchestrateContentType(): void
    {
        $visibilityStrategy = new AllowAllStrategy();

        $editorialOrchestrator = new PipelineEditorialOrchestrator(
            $this->pipeline,
            $visibilityStrategy,
            $this->responseFactory,
            'editorial',
        );

        $previumOrchestrator = new PipelineEditorialOrchestrator(
            $this->pipeline,
            $visibilityStrategy,
            $this->responseFactory,
            'previum',
        );

        self::assertSame('editorial', $editorialOrchestrator->canOrchestrate());
        self::assertSame('previum', $previumOrchestrator->canOrchestrate());
    }

    private function buildFullEditorialResponse(): EditorialResponse
    {
        $titles = new TitlesResponse(
            preTitle: 'Breaking',
            title: 'Test Editorial Title',
            urlTitle: 'test-editorial-title',
        );

        $bodyElements = [
            new BodyElementResponse(
                type: 'paragraph',
                content: '<p>First paragraph of the editorial body.</p>',
            ),
            new BodyElementResponse(
                type: 'subhead',
                content: 'A Subheading',
                level: 2,
            ),
        ];

        $body = new BodyResponse(
            type: 'body',
            elements: $bodyElements,
        );

        $signatures = [
            new SignatureResponse(
                aliasId: 'alias-001',
                name: 'Jane Reporter',
                description: 'Senior correspondent',
                url: 'https://www.example.com/journalists/jane-reporter',
                photoUrl: 'https://images.example.com/journalists/jane.jpg',
                twitter: '@janereporter',
            ),
        ];

        $section = new SectionResponse(
            id: 'sec-100',
            name: 'Economy',
            url: 'https://www.example.com/sections/economy',
            siteId: 'site-1',
        );

        $tags = [
            new TagResponse(
                id: 'tag-001',
                name: 'Politics',
                url: 'https://www.example.com/tags/politics',
            ),
            new TagResponse(
                id: 'tag-002',
                name: 'Finance',
                url: 'https://www.example.com/tags/finance',
            ),
        ];

        $multimedia = new MultimediaResponse(
            type: 'photo',
            id: 'mm-001',
            url: 'https://images.example.com/photos/mm-001.jpg',
            caption: 'Opening photo caption',
            credit: 'EFE',
            metadata: ['width' => 1200, 'height' => 800],
        );

        $standfirst = [
            'title' => 'Key Points',
            'items' => [
                'First key point of the editorial',
                'Second key point of the editorial',
            ],
        ];

        $recommendedEditorials = [
            'rec-001' => [
                'editorial' => ['id' => 'rec-001', 'title' => 'Related Article 1'],
                'section' => ['id' => 'sec-200', 'name' => 'Technology'],
                'signatures' => [],
                'multimediaId' => 'mm-rec-001',
            ],
        ];

        return new EditorialResponse(
            id: self::EDITORIAL_ID,
            url: self::EDITORIAL_URL,
            titles: $titles,
            lead: 'This is the editorial lead text.',
            publicationDate: self::PUBLICATION_DATE,
            updatedOn: self::UPDATED_ON,
            endOn: '2025-12-31 23:59:59',
            typeId: '1',
            typeName: 'news',
            indexable: true,
            deleted: false,
            published: true,
            closingModeId: '0',
            commentable: true,
            isBrand: false,
            isAmazonOnsite: false,
            contentType: 'editorial',
            canonicalEditorialId: '11111',
            urlDate: '2025-03-15 00:00:00',
            countWords: 850,
            body: $body,
            signatures: $signatures,
            section: $section,
            tags: $tags,
            countComments: 42,
            multimedia: $multimedia,
            standfirst: $standfirst,
            recommendedEditorials: $recommendedEditorials,
        );
    }
}
