<?php

declare(strict_types=1);

namespace App\Tests\Unit\Previum\Token;

use App\Previum\Exception\InvalidTokenException;
use App\Previum\Token\FirebaseJwtDecoder;
use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FirebaseJwtDecoder::class)]
class FirebaseJwtDecoderTest extends TestCase
{
    private const SECRET = 'test-secret-key';
    private const ALGORITHM = 'HS256';

    private FirebaseJwtDecoder $decoder;

    protected function setUp(): void
    {
        $this->decoder = new FirebaseJwtDecoder(self::SECRET, self::ALGORITHM);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->decoder);
    }

    #[Test]
    public function decodeShouldReturnPayloadArrayForValidJwt(): void
    {
        $payload = [
            'sub' => 'user-123',
            'user_type' => 'previum',
            'iat' => time(),
        ];

        $jwt = JWT::encode($payload, self::SECRET, self::ALGORITHM);

        $decoded = $this->decoder->decode($jwt);

        $this->assertSame('user-123', $decoded['sub']);
        $this->assertSame('previum', $decoded['user_type']);
    }

    #[Test]
    public function decodeShouldThrowInvalidTokenExceptionForInvalidJwt(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Unauthorized');
        $this->expectExceptionCode(401);

        $this->decoder->decode('invalid.jwt.token');
    }

    #[Test]
    public function decodeShouldThrowInvalidTokenExceptionForWrongSecret(): void
    {
        $this->expectException(InvalidTokenException::class);

        $payload = ['sub' => 'user-123'];
        $jwt = JWT::encode($payload, 'wrong-secret', self::ALGORITHM);

        $this->decoder->decode($jwt);
    }
}
