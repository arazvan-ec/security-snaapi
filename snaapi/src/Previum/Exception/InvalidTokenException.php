<?php

declare(strict_types=1);

namespace App\Previum\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class InvalidTokenException extends AuthenticationException
{
    private const DEFAULT_MESSAGE = 'Unauthorized';

    public function __construct(string $message = self::DEFAULT_MESSAGE, int $code = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getMessageKey(): string
    {
        return $this->getMessage();
    }
}
