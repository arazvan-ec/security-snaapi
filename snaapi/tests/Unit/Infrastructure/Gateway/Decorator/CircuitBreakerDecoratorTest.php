<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Gateway\Decorator;

use App\Domain\Port\Gateway\EditorialGatewayInterface;
use App\Infrastructure\Gateway\Decorator\CircuitBreakerDecorator;
use Ec\Editorial\Domain\Model\NewsBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(CircuitBreakerDecorator::class)]
final class CircuitBreakerDecoratorTest extends TestCase
{
    private EditorialGatewayInterface $innerGateway;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->innerGateway = $this->createMock(EditorialGatewayInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    #[Test]
    public function it_passes_requests_when_circuit_is_closed(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $this->innerGateway->method('findById')->willReturn($editorial);

        $decorator = new CircuitBreakerDecorator($this->innerGateway, $this->logger);

        $result = $decorator->findById('123');

        self::assertSame($editorial, $result);
        self::assertSame('closed', $decorator->getState());
    }

    #[Test]
    public function it_opens_circuit_after_failure_threshold(): void
    {
        $this->innerGateway->method('findById')
            ->willThrowException(new \RuntimeException('Service unavailable'));

        $decorator = new CircuitBreakerDecorator(
            $this->innerGateway,
            $this->logger,
            failureThreshold: 3
        );

        // Trigger 3 failures
        for ($i = 0; $i < 3; ++$i) {
            try {
                $decorator->findById('123');
            } catch (\RuntimeException) {
                // Expected
            }
        }

        self::assertSame('open', $decorator->getState());
        self::assertSame(3, $decorator->getFailureCount());
    }

    #[Test]
    public function it_returns_null_when_circuit_is_open(): void
    {
        $this->innerGateway->method('findById')
            ->willThrowException(new \RuntimeException('Service unavailable'));

        $decorator = new CircuitBreakerDecorator(
            $this->innerGateway,
            $this->logger,
            failureThreshold: 1
        );

        // Open the circuit
        try {
            $decorator->findById('123');
        } catch (\RuntimeException) {
            // Expected
        }

        // Circuit should be open now
        self::assertSame('open', $decorator->getState());

        // Next request should return null without calling inner
        $this->innerGateway->expects(self::never())->method('findById');

        $result = $decorator->findById('456');

        self::assertNull($result);
    }

    #[Test]
    public function it_resets_failure_count_on_success(): void
    {
        $editorial = $this->createMock(NewsBase::class);

        $callCount = 0;
        $this->innerGateway->method('findById')
            ->willReturnCallback(function () use (&$callCount, $editorial) {
                ++$callCount;
                if ($callCount < 3) {
                    throw new \RuntimeException('Temporary failure');
                }

                return $editorial;
            });

        $decorator = new CircuitBreakerDecorator(
            $this->innerGateway,
            $this->logger,
            failureThreshold: 5
        );

        // 2 failures
        for ($i = 0; $i < 2; ++$i) {
            try {
                $decorator->findById('123');
            } catch (\RuntimeException) {
            }
        }

        self::assertSame(2, $decorator->getFailureCount());

        // 1 success
        $decorator->findById('123');

        // Failure count should be reset
        self::assertSame(0, $decorator->getFailureCount());
        self::assertSame('closed', $decorator->getState());
    }

    #[Test]
    public function it_can_be_manually_reset(): void
    {
        $this->innerGateway->method('findById')
            ->willThrowException(new \RuntimeException('Failure'));

        $decorator = new CircuitBreakerDecorator(
            $this->innerGateway,
            $this->logger,
            failureThreshold: 1
        );

        try {
            $decorator->findById('123');
        } catch (\RuntimeException) {
        }

        self::assertSame('open', $decorator->getState());

        $decorator->reset();

        self::assertSame('closed', $decorator->getState());
        self::assertSame(0, $decorator->getFailureCount());
    }
}
