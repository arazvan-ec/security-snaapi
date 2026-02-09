<?php

declare(strict_types=1);

namespace App\Previum\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class InsufficientPermissionsException extends AuthenticationException
{
    private const DEFAULT_MESSAGE = 'Forbidden: insufficient permissions';

    public function __construct(string $message = self::DEFAULT_MESSAGE, int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getMessageKey(): string
    {
        return $this->getMessage();
    }
}
