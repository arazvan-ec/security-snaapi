<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Strategy\Visibility;

use App\Application\Strategy\Visibility\PublishedOnlyStrategy;
use App\Exception\EditorialNotPublishedYetException;
use Ec\Editorial\Domain\Model\NewsBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PublishedOnlyStrategy::class)]
final class PublishedOnlyStrategyTest extends TestCase
{
    private PublishedOnlyStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new PublishedOnlyStrategy();
    }

    protected function tearDown(): void
    {
        unset($this->strategy);
    }

    #[Test]
    public function it_throws_exception_for_invisible_editorial(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('isVisible')->willReturn(false);

        $this->expectException(EditorialNotPublishedYetException::class);

        $this->strategy->checkVisibility($editorial);
    }

    #[Test]
    public function it_allows_visible_editorial(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('isVisible')->willReturn(true);

        $this->strategy->checkVisibility($editorial);

        $this->addToAssertionCount(1);
    }
}
