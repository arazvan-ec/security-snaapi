<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateway\Decorator;

use App\Domain\Port\Gateway\EditorialGatewayInterface;
use Ec\Editorial\Domain\Model\NewsBase;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Decorator that adds caching to any EditorialGatewayInterface implementation.
 *
 * Uses PSR-6 cache for storing results with configurable TTL.
 */
final class CachedGatewayDecorator implements EditorialGatewayInterface
{
    private const CACHE_PREFIX = 'editorial_';
    private const DEFAULT_TTL = 300; // 5 minutes

    public function __construct(
        private readonly EditorialGatewayInterface $inner,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly int $ttl = self::DEFAULT_TTL,
    ) {
    }

    public function findById(string $id): ?NewsBase
    {
        $cacheKey = $this->getCacheKey($id);
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $this->logger->debug('Cache hit for editorial', ['id' => $id]);

            return $cacheItem->get();
        }

        $this->logger->debug('Cache miss for editorial', ['id' => $id]);

        $editorial = $this->inner->findById($id);

        if (null !== $editorial) {
            $cacheItem->set($editorial);
            $cacheItem->expiresAfter($this->ttl);
            $this->cache->save($cacheItem);
        }

        return $editorial;
    }

    public function findByIdAsync(string $id): PromiseInterface
    {
        $cacheKey = $this->getCacheKey($id);
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $this->logger->debug('Cache hit (async) for editorial', ['id' => $id]);

            return new FulfilledPromise($cacheItem->get());
        }

        return $this->inner->findByIdAsync($id)->then(
            function (?NewsBase $editorial) use ($cacheKey) {
                if (null !== $editorial) {
                    $cacheItem = $this->cache->getItem($cacheKey);
                    $cacheItem->set($editorial);
                    $cacheItem->expiresAfter($this->ttl);
                    $this->cache->save($cacheItem);
                }

                return $editorial;
            }
        );
    }

    public function invalidate(string $id): void
    {
        $this->cache->deleteItem($this->getCacheKey($id));
        $this->logger->debug('Cache invalidated for editorial', ['id' => $id]);
    }

    private function getCacheKey(string $id): string
    {
        return self::CACHE_PREFIX . $id;
    }
}
