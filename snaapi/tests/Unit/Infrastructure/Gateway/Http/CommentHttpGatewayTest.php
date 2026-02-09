<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Gateway\Http;

use App\Ec\Snaapi\Infrastructure\Client\Http\QueryLegacyClient;
use App\Infrastructure\Gateway\Http\CommentHttpGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommentHttpGateway::class)]
final class CommentHttpGatewayTest extends TestCase
{
    private QueryLegacyClient&MockObject $legacyClient;
    private CommentHttpGateway $gateway;

    protected function setUp(): void
    {
        $this->legacyClient = $this->createMock(QueryLegacyClient::class);
        $this->gateway = new CommentHttpGateway($this->legacyClient);
    }

    protected function tearDown(): void
    {
        unset($this->legacyClient, $this->gateway);
    }

    #[Test]
    public function it_returns_comment_count_from_legacy_client(): void
    {
        $editorialId = '12345';
        $this->legacyClient
            ->expects(self::once())
            ->method('findCommentsByEditorialId')
            ->with($editorialId)
            ->willReturn(['options' => ['totalrecords' => 42]]);

        $result = $this->gateway->findCommentCountByEditorialId($editorialId);

        self::assertSame(42, $result);
    }

    #[Test]
    public function it_returns_zero_when_totalrecords_is_missing(): void
    {
        $this->legacyClient
            ->method('findCommentsByEditorialId')
            ->willReturn(['options' => []]);

        $result = $this->gateway->findCommentCountByEditorialId('12345');

        self::assertSame(0, $result);
    }

    #[Test]
    public function it_returns_zero_on_exception(): void
    {
        $this->legacyClient
            ->method('findCommentsByEditorialId')
            ->willThrowException(new \RuntimeException('Connection failed'));

        $result = $this->gateway->findCommentCountByEditorialId('12345');

        self::assertSame(0, $result);
    }
}
