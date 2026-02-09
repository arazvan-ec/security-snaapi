<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orchestrator\Chain;

use App\Application\DTO\Response\EditorialResponse;
use App\Application\Factory\Response\EditorialResponseFactory;
use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnrichmentPipeline;
use App\Application\Strategy\Visibility\VisibilityStrategyInterface;
use App\Exception\EditorialNotPublishedYetException;
use App\Orchestrator\Chain\PipelineEditorialOrchestrator;
use Ec\Editorial\Domain\Model\NewsBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(PipelineEditorialOrchestrator::class)]
final class PipelineEditorialOrchestratorTest extends TestCase
{
    private EnrichmentPipeline&MockObject $pipeline;
    private VisibilityStrategyInterface&MockObject $visibilityStrategy;
    private EditorialResponseFactory&MockObject $responseFactory;
    private PipelineEditorialOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->pipeline = $this->createMock(EnrichmentPipeline::class);
        $this->visibilityStrategy = $this->createMock(VisibilityStrategyInterface::class);
        $this->responseFactory = $this->createMock(EditorialResponseFactory::class);

        $this->orchestrator = new PipelineEditorialOrchestrator(
            $this->pipeline,
            $this->visibilityStrategy,
            $this->responseFactory,
            'editorial',
        );
    }

    protected function tearDown(): void
    {
        unset($this->pipeline, $this->visibilityStrategy, $this->responseFactory, $this->orchestrator);
    }

    #[Test]
    public function it_executes_pipeline_and_returns_response(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $expectedData = ['id' => '123', 'title' => 'Test Editorial'];

        $this->pipeline->method('process')
            ->willReturnCallback(function (EditorialContext $ctx) use ($editorial): EditorialContext {
                $ctx->setEditorial($editorial);

                return $ctx;
            });

        $response = $this->createMock(EditorialResponse::class);
        $response->method('jsonSerialize')->willReturn($expectedData);

        $this->responseFactory->method('create')->willReturn($response);

        $request = Request::create('/editorials/123');
        $request->attributes->set('id', '123');

        $result = $this->orchestrator->execute($request);

        self::assertSame($expectedData, $result);
    }

    #[Test]
    public function it_applies_visibility_strategy(): void
    {
        $editorial = $this->createMock(NewsBase::class);

        $this->pipeline->method('process')
            ->willReturnCallback(function (EditorialContext $ctx) use ($editorial): EditorialContext {
                $ctx->setEditorial($editorial);

                return $ctx;
            });

        $this->visibilityStrategy->expects(self::once())
            ->method('checkVisibility')
            ->with($editorial);

        $response = $this->createMock(EditorialResponse::class);
        $response->method('jsonSerialize')->willReturn([]);

        $this->responseFactory->method('create')->willReturn($response);

        $request = Request::create('/editorials/123');
        $request->attributes->set('id', '123');

        $this->orchestrator->execute($request);
    }

    #[Test]
    public function it_throws_when_editorial_not_in_context(): void
    {
        $this->pipeline->method('process')
            ->willReturnCallback(function (EditorialContext $ctx): EditorialContext {
                return $ctx;
            });

        $request = Request::create('/editorials/123');
        $request->attributes->set('id', '123');

        $this->expectException(\RuntimeException::class);

        $this->orchestrator->execute($request);
    }

    #[Test]
    public function it_throws_when_visibility_check_fails(): void
    {
        $editorial = $this->createMock(NewsBase::class);

        $this->pipeline->method('process')
            ->willReturnCallback(function (EditorialContext $ctx) use ($editorial): EditorialContext {
                $ctx->setEditorial($editorial);

                return $ctx;
            });

        $this->visibilityStrategy->method('checkVisibility')
            ->willThrowException(new EditorialNotPublishedYetException());

        $request = Request::create('/editorials/123');
        $request->attributes->set('id', '123');

        $this->expectException(EditorialNotPublishedYetException::class);

        $this->orchestrator->execute($request);
    }

    #[Test]
    public function it_can_orchestrate_returns_content_type(): void
    {
        self::assertSame('editorial', $this->orchestrator->canOrchestrate());
    }

    #[Test]
    public function it_can_orchestrate_returns_previum(): void
    {
        $orchestrator = new PipelineEditorialOrchestrator(
            $this->pipeline,
            $this->visibilityStrategy,
            $this->responseFactory,
            'previum',
        );

        self::assertSame('previum', $orchestrator->canOrchestrate());
    }
}
