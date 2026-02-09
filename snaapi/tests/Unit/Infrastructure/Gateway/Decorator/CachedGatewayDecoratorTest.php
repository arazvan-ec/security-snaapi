<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Gateway\Decorator;

use App\Domain\Port\Gateway\EditorialGatewayInterface;
use App\Infrastructure\Gateway\Decorator\CachedGatewayDecorator;
use Ec\Editorial\Domain\Model\NewsBase;
use GuzzleHttp\Promise\FulfilledPromise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(CachedGatewayDecorator::class)]
final class CachedGatewayDecoratorTest extends TestCase
{
    private EditorialGatewayInterface $innerGateway;
    private CacheItemPoolInterface $cache;
    private LoggerInterface $logger;
    private CachedGatewayDecorator $decorator;

    protected function setUp(): void
    {
        $this->innerGateway = $this->createMock(EditorialGatewayInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->decorator = new CachedGatewayDecorator(
            $this->innerGateway,
            $this->cache,
            $this->logger,
            300
        );
    }

    #[Test]
    public function it_returns_cached_editorial_on_cache_hit(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItem->method('isHit')->willReturn(true);
        $cacheItem->method('get')->willReturn($editorial);

        $this->cache->method('getItem')->willReturn($cacheItem);

        $this->innerGateway->expects(self::never())->method('findById');

        $result = $this->decorator->findById('123');

        self::assertSame($editorial, $result);
    }

    #[Test]
    public function it_fetches_from_gateway_on_cache_miss(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects(self::once())->method('set')->with($editorial);
        $cacheItem->expects(self::once())->method('expiresAfter')->with(300);

        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects(self::once())->method('save')->with($cacheItem);

        $this->innerGateway->method('findById')->with('123')->willReturn($editorial);

        $result = $this->decorator->findById('123');

        self::assertSame($editorial, $result);
    }

    #[Test]
    public function it_does_not_cache_null_results(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItem->method('isHit')->willReturn(false);
        $cacheItem->expects(self::never())->method('set');

        $this->cache->method('getItem')->willReturn($cacheItem);
        $this->cache->expects(self::never())->method('save');

        $this->innerGateway->method('findById')->willReturn(null);

        $result = $this->decorator->findById('123');

        self::assertNull($result);
    }

    #[Test]
    public function it_returns_cached_result_for_async_on_cache_hit(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);

        $cacheItem->method('isHit')->willReturn(true);
        $cacheItem->method('get')->willReturn($editorial);

        $this->cache->method('getItem')->willReturn($cacheItem);

        $this->innerGateway->expects(self::never())->method('findByIdAsync');

        $promise = $this->decorator->findByIdAsync('123');
        $result = $promise->wait();

        self::assertSame($editorial, $result);
    }

    #[Test]
    public function it_invalidates_cache(): void
    {
        $this->cache->expects(self::once())
            ->method('deleteItem')
            ->with('editorial_123');

        $this->decorator->invalidate('123');
    }
}
