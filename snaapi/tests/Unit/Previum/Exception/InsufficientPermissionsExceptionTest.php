<?php

declare(strict_types=1);

namespace App\Tests\Unit\Previum\Exception;

use App\Previum\Exception\InsufficientPermissionsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[CoversClass(InsufficientPermissionsException::class)]
class InsufficientPermissionsExceptionTest extends TestCase
{
    #[Test]
    public function defaultMessageShouldBeForbiddenInsufficientPermissions(): void
    {
        $exception = new InsufficientPermissionsException();

        $this->assertSame('Forbidden: insufficient permissions', $exception->getMessage());
    }

    #[Test]
    public function defaultCodeShouldBe403(): void
    {
        $exception = new InsufficientPermissionsException();

        $this->assertSame(403, $exception->getCode());
    }

    #[Test]
    public function getMessageKeyShouldReturnTheMessage(): void
    {
        $exception = new InsufficientPermissionsException();

        $this->assertSame('Forbidden: insufficient permissions', $exception->getMessageKey());
    }

    #[Test]
    public function shouldBeInstanceOfAuthenticationException(): void
    {
        $exception = new InsufficientPermissionsException();

        $this->assertInstanceOf(AuthenticationException::class, $exception);
    }
}
