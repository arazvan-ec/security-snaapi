<?php

declare(strict_types=1);

namespace App\Previum\Token;

use App\Previum\Exception\InvalidTokenException;

interface TokenValidatorInterface
{
    /**
     * Validate a JWT token against the external authentication microservice.
     *
     * @return array<string, mixed> Decoded payload from the response JWT
     *
     * @throws InvalidTokenException
     */
    public function validate(string $token): array;
}
