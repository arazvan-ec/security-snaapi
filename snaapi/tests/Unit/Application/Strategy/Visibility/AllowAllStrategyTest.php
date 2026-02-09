<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Strategy\Visibility;

use App\Application\Strategy\Visibility\AllowAllStrategy;
use Ec\Editorial\Domain\Model\NewsBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AllowAllStrategy::class)]
final class AllowAllStrategyTest extends TestCase
{
    private AllowAllStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new AllowAllStrategy();
    }

    protected function tearDown(): void
    {
        unset($this->strategy);
    }

    #[Test]
    public function it_allows_invisible_editorial(): void
    {
        $editorial = $this->createMock(NewsBase::class);
        $editorial->method('isVisible')->willReturn(false);

        $this->strategy->checkVisibility($editorial);

        $this->addToAssertionCount(1);
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
