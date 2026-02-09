<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Pipeline;

use App\Application\Pipeline\EditorialContext;
use App\Application\Pipeline\EnricherInterface;
use App\Application\Pipeline\EnrichmentPipeline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(EnrichmentPipeline::class)]
final class EnrichmentPipelineTest extends TestCase
{
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    #[Test]
    public function it_processes_enrichers_in_priority_order(): void
    {
        $executionOrder = [];

        $enricher1 = $this->createEnricher(50, true, function () use (&$executionOrder): void {
            $executionOrder[] = 'enricher1';
        });

        $enricher2 = $this->createEnricher(100, true, function () use (&$executionOrder): void {
            $executionOrder[] = 'enricher2';
        });

        $enricher3 = $this->createEnricher(75, true, function () use (&$executionOrder): void {
            $executionOrder[] = 'enricher3';
        });

        $pipeline = new EnrichmentPipeline([$enricher1, $enricher2, $enricher3], $this->logger);

        $context = new EditorialContext('123');
        $result = $pipeline->process($context);

        self::assertSame($context, $result);
        self::assertSame(['enricher2', 'enricher3', 'enricher1'], $executionOrder);
    }

    #[Test]
    public function it_skips_enrichers_that_do_not_support_context(): void
    {
        $enricher1 = $this->createEnricher(100, false, fn () => self::fail('Should not be called'));
        $enricher2 = $this->createEnricher(50, true, fn () => null);

        $this->logger->expects(self::once())
            ->method('debug')
            ->with('Enricher skipped (not supported)', self::anything());

        $pipeline = new EnrichmentPipeline([$enricher1, $enricher2], $this->logger);

        $pipeline->process(new EditorialContext('123'));
    }

    #[Test]
    public function it_logs_successful_enrichment(): void
    {
        $enricher = $this->createEnricher(100, true, fn () => null);

        $this->logger->expects(self::once())
            ->method('info')
            ->with('Enrichment completed', self::anything());

        $pipeline = new EnrichmentPipeline([$enricher], $this->logger);

        $pipeline->process(new EditorialContext('123'));
    }

    #[Test]
    public function it_handles_enricher_exceptions_gracefully(): void
    {
        $executionOrder = [];

        $failingEnricher = $this->createEnricher(100, true, function (): void {
            throw new \RuntimeException('Test exception');
        });

        $successfulEnricher = $this->createEnricher(50, true, function () use (&$executionOrder): void {
            $executionOrder[] = 'successful';
        });

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Enricher failed', self::callback(function ($context) {
                return 'Test exception' === $context['error'];
            }));

        $pipeline = new EnrichmentPipeline([$failingEnricher, $successfulEnricher], $this->logger);

        $pipeline->process(new EditorialContext('123'));

        self::assertSame(['successful'], $executionOrder);
    }

    #[Test]
    public function it_returns_context_unchanged_when_no_enrichers(): void
    {
        $pipeline = new EnrichmentPipeline([], $this->logger);

        $context = new EditorialContext('123');
        $result = $pipeline->process($context);

        self::assertSame($context, $result);
    }

    #[Test]
    public function it_enriches_context_with_data(): void
    {
        $enricher = $this->createEnricher(100, true, function (EditorialContext $ctx): void {
            $ctx->setCommentsCount(42);
        });

        $pipeline = new EnrichmentPipeline([$enricher], $this->logger);

        $context = new EditorialContext('123');
        $result = $pipeline->process($context);

        self::assertSame(42, $result->commentsCount());
    }

    private function createEnricher(int $priority, bool $supports, callable $enrichCallback): EnricherInterface
    {
        $enricher = $this->createMock(EnricherInterface::class);

        $enricher->method('priority')->willReturn($priority);
        $enricher->method('supports')->willReturn($supports);
        $enricher->method('enrich')->willReturnCallback($enrichCallback);

        return $enricher;
    }
}
