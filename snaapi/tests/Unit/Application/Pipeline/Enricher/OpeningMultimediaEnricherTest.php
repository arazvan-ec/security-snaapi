<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Pipeline\Enricher;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\Enricher\OpeningMultimediaEnricher;
use App\Domain\Port\Gateway\MultimediaGatewayInterface;
use App\Orchestrator\Chain\Multimedia\MultimediaOrchestratorHandler;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(OpeningMultimediaEnricher::class)]
final class OpeningMultimediaEnricherTest extends TestCase
{
    private MultimediaGatewayInterface&MockObject $gateway;
    private MultimediaOrchestratorHandler&MockObject $orchestratorHandler;
    private LoggerInterface&MockObject $logger;
    private OpeningMultimediaEnricher $enricher;

    protected function setUp(): void
    {
        $this->gateway = $this->createMock(MultimediaGatewayInterface::class);
        $this->orchestratorHandler = $this->createMock(MultimediaOrchestratorHandler::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->enricher = new OpeningMultimediaEnricher(
            $this->gateway,
            $this->orchestratorHandler,
            $this->logger,
        );
    }

    #[Test]
    public function it_has_priority_75(): void
    {
        self::assertSame(75, $this->enricher->priority());
    }

    #[Test]
    public function it_supports_context_with_editorial(): void
    {
        $context = new EditorialContext('123');
        $context->setEditorial($this->createMock(NewsBase::class));

        self::assertTrue($this->enricher->supports($context));
    }

    #[Test]
    public function it_does_not_support_context_without_editorial(): void
    {
        $context = new EditorialContext('123');

        self::assertFalse($this->enricher->supports($context));
    }

    #[Test]
    public function it_enriches_context_with_opening_multimedia(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('multimedia-456');

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('opening')->willReturn($opening);

        $multimedia = $this->createMock(Multimedia::class);
        $expectedResult = ['type' => 'photo', 'data' => ['id' => 'multimedia-456']];

        $this->gateway->expects(self::once())
            ->method('findOpeningMultimediaById')
            ->with('multimedia-456')
            ->willReturn($multimedia);

        $this->orchestratorHandler->expects(self::once())
            ->method('handler')
            ->with($multimedia)
            ->willReturn($expectedResult);

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        self::assertSame($expectedResult, $context->multimediaOpening());
    }

    #[Test]
    public function it_returns_early_when_editorial_is_null(): void
    {
        $context = new EditorialContext('123');

        $this->gateway->expects(self::never())->method('findOpeningMultimediaById');
        $this->orchestratorHandler->expects(self::never())->method('handler');

        $this->enricher->enrich($context);

        self::assertSame([], $context->multimediaOpening());
    }

    #[Test]
    public function it_returns_early_when_opening_is_null(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('opening')->willReturn(null);

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->gateway->expects(self::never())->method('findOpeningMultimediaById');

        $this->enricher->enrich($context);

        self::assertSame([], $context->multimediaOpening());
    }

    #[Test]
    public function it_returns_early_when_opening_multimedia_id_is_empty(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('');

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('opening')->willReturn($opening);

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->gateway->expects(self::never())->method('findOpeningMultimediaById');

        $this->enricher->enrich($context);

        self::assertSame([], $context->multimediaOpening());
    }

    #[Test]
    public function it_returns_early_when_gateway_returns_null(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('multimedia-456');

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('opening')->willReturn($opening);

        $this->gateway->expects(self::once())
            ->method('findOpeningMultimediaById')
            ->with('multimedia-456')
            ->willReturn(null);

        $this->orchestratorHandler->expects(self::never())->method('handler');

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        self::assertSame([], $context->multimediaOpening());
    }

    #[Test]
    public function it_logs_warning_when_orchestrator_handler_throws(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('multimedia-456');

        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('opening')->willReturn($opening);

        $multimedia = $this->createMock(Multimedia::class);

        $this->gateway->expects(self::once())
            ->method('findOpeningMultimediaById')
            ->with('multimedia-456')
            ->willReturn($multimedia);

        $this->orchestratorHandler->expects(self::once())
            ->method('handler')
            ->with($multimedia)
            ->willThrowException(new \RuntimeException('Orchestrator type not found'));

        $this->logger->expects(self::once())
            ->method('warning')
            ->with('Failed to process opening multimedia', self::callback(
                fn (array $logContext) => 'multimedia-456' === $logContext['multimediaId']
                    && 'Orchestrator type not found' === $logContext['error']
            ));

        $context = new EditorialContext('123');
        $context->setEditorial($editorial);

        $this->enricher->enrich($context);

        self::assertSame([], $context->multimediaOpening());
    }
}
