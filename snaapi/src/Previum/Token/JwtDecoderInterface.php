<?php

declare(strict_types=1);

namespace App\Previum\Token;

use App\Previum\Exception\InvalidTokenException;

interface JwtDecoderInterface
{
    /**
     * @return array<string, mixed>
     *
     * @throws InvalidTokenException
     */
    public function decode(string $jwt): array;
}
