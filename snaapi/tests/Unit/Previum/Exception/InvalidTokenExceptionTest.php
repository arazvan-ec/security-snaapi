<?php

declare(strict_types=1);

namespace App\Tests\Unit\Previum\Exception;

use App\Previum\Exception\InvalidTokenException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[CoversClass(InvalidTokenException::class)]
class InvalidTokenExceptionTest extends TestCase
{
    #[Test]
    public function defaultMessageShouldBeUnauthorized(): void
    {
        $exception = new InvalidTokenException();

        $this->assertSame('Unauthorized', $exception->getMessage());
    }

    #[Test]
    public function defaultCodeShouldBe401(): void
    {
        $exception = new InvalidTokenException();

        $this->assertSame(401, $exception->getCode());
    }

    #[Test]
    public function getMessageKeyShouldReturnTheMessage(): void
    {
        $exception = new InvalidTokenException();

        $this->assertSame('Unauthorized', $exception->getMessageKey());
    }

    #[Test]
    public function canPassCustomMessage(): void
    {
        $customMessage = 'Token has expired';
        $exception = new InvalidTokenException($customMessage);

        $this->assertSame($customMessage, $exception->getMessage());
        $this->assertSame($customMessage, $exception->getMessageKey());
    }

    #[Test]
    public function shouldBeInstanceOfAuthenticationException(): void
    {
        $exception = new InvalidTokenException();

        $this->assertInstanceOf(AuthenticationException::class, $exception);
    }
}
