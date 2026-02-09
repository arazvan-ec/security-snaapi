<?php

declare(strict_types=1);

namespace App\Previum\Token;

use App\Previum\Exception\InvalidTokenException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class FirebaseJwtDecoder implements JwtDecoderInterface
{
    public function __construct(
        private readonly string $secret,
        private readonly string $algorithm = 'HS256',
    ) {
    }

    /** @return array<string, mixed> */
    public function decode(string $jwt): array
    {
        try {
            $decoded = JWT::decode($jwt, new Key($this->secret, $this->algorithm));

            return (array) $decoded;
        } catch (\Throwable $e) {
            throw new InvalidTokenException('Unauthorized', 401, $e);
        }
    }
}
