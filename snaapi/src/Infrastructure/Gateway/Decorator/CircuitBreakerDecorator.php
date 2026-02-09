<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateway\Decorator;

use App\Domain\Port\Gateway\EditorialGatewayInterface;
use Ec\Editorial\Domain\Model\NewsBase;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Log\LoggerInterface;

/**
 * Decorator that implements Circuit Breaker pattern for fault tolerance.
 *
 * States:
 * - CLOSED: Normal operation, requests pass through
 * - OPEN: Circuit is open, requests fail fast
 * - HALF_OPEN: Testing if service recovered
 */
final class CircuitBreakerDecorator implements EditorialGatewayInterface
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    private const DEFAULT_FAILURE_THRESHOLD = 5;
    private const DEFAULT_RECOVERY_TIMEOUT = 30; // seconds

    private string $state = self::STATE_CLOSED;
    private int $failureCount = 0;
    private ?int $lastFailureTime = null;

    public function __construct(
        private readonly EditorialGatewayInterface $inner,
        private readonly LoggerInterface $logger,
        private readonly int $failureThreshold = self::DEFAULT_FAILURE_THRESHOLD,
        private readonly int $recoveryTimeout = self::DEFAULT_RECOVERY_TIMEOUT,
    ) {
    }

    public function findById(string $id): ?NewsBase
    {
        if (!$this->canMakeRequest()) {
            $this->logger->warning('Circuit breaker OPEN, rejecting request', ['id' => $id]);

            return null;
        }

        try {
            $result = $this->inner->findById($id);
            $this->recordSuccess();

            return $result;
        } catch (\Throwable $e) {
            $this->recordFailure($e);
            throw $e;
        }
    }

    public function findByIdAsync(string $id): PromiseInterface
    {
        if (!$this->canMakeRequest()) {
            $this->logger->warning('Circuit breaker OPEN, rejecting async request', ['id' => $id]);

            return new RejectedPromise(
                new \RuntimeException('Circuit breaker is open')
            );
        }

        return $this->inner->findByIdAsync($id)->then(
            function ($result) {
                $this->recordSuccess();

                return $result;
            },
            function (\Throwable $e) {
                $this->recordFailure($e);
                throw $e;
            }
        );
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    public function reset(): void
    {
        $this->state = self::STATE_CLOSED;
        $this->failureCount = 0;
        $this->lastFailureTime = null;
        $this->logger->info('Circuit breaker reset');
    }

    private function canMakeRequest(): bool
    {
        if (self::STATE_CLOSED === $this->state) {
            return true;
        }

        if (self::STATE_OPEN === $this->state) {
            if ($this->shouldAttemptRecovery()) {
                $this->state = self::STATE_HALF_OPEN;
                $this->logger->info('Circuit breaker entering HALF_OPEN state');

                return true;
            }

            return false;
        }

        // HALF_OPEN - allow single request to test recovery
        return true;
    }

    private function shouldAttemptRecovery(): bool
    {
        if (null === $this->lastFailureTime) {
            return true;
        }

        return (time() - $this->lastFailureTime) >= $this->recoveryTimeout;
    }

    private function recordSuccess(): void
    {
        if (self::STATE_HALF_OPEN === $this->state) {
            $this->logger->info('Circuit breaker recovery successful, closing circuit');
        }

        $this->state = self::STATE_CLOSED;
        $this->failureCount = 0;
        $this->lastFailureTime = null;
    }

    private function recordFailure(\Throwable $e): void
    {
        ++$this->failureCount;
        $this->lastFailureTime = time();

        $this->logger->warning('Circuit breaker recorded failure', [
            'count' => $this->failureCount,
            'threshold' => $this->failureThreshold,
            'error' => $e->getMessage(),
        ]);

        if ($this->failureCount >= $this->failureThreshold) {
            $this->state = self::STATE_OPEN;
            $this->logger->error('Circuit breaker OPENED due to failures', [
                'failureCount' => $this->failureCount,
            ]);
        }
    }
}
