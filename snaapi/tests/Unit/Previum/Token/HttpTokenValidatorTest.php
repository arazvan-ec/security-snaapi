<?php

declare(strict_types=1);

namespace App\Tests\Unit\Previum\Token;

use App\Previum\Exception\InvalidTokenException;
use App\Previum\Token\HttpTokenValidator;
use App\Previum\Token\JwtDecoderInterface;
use Http\Client\HttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(HttpTokenValidator::class)]
class HttpTokenValidatorTest extends TestCase
{
    private const HOST = 'https://auth.example.com';
    private const ENDPOINT = '/api/validate';

    private HttpClient&MockObject $httpClient;
    private JwtDecoderInterface&MockObject $jwtDecoder;
    private HttpTokenValidator $validator;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->jwtDecoder = $this->createMock(JwtDecoderInterface::class);

        $messageFactory = new Psr17Factory();

        $this->validator = new HttpTokenValidator(
            $this->httpClient,
            $messageFactory,
            $this->jwtDecoder,
            self::HOST,
            self::ENDPOINT,
        );

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->httpClient, $this->jwtDecoder, $this->validator);
    }

    #[Test]
    public function validateShouldSendPostRequestAndReturnDecodedPayload(): void
    {
        $token = 'incoming-token';
        $responseJwt = 'response.jwt.token';
        $decodedPayload = ['sub' => 'user-123', 'user_type' => 'previum'];

        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody
            ->method('getContents')
            ->willReturn(json_encode(['token' => $responseJwt], JSON_THROW_ON_ERROR));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $this->jwtDecoder
            ->expects($this->once())
            ->method('decode')
            ->with($responseJwt)
            ->willReturn($decodedPayload);

        $result = $this->validator->validate($token);

        $this->assertSame($decodedPayload, $result);
    }

    #[Test]
    public function validateShouldThrowInvalidTokenExceptionWhenHttpResponseIsError(): void
    {
        $this->expectException(InvalidTokenException::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(401);

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $this->jwtDecoder->expects($this->never())->method('decode');

        $this->validator->validate('some-token');
    }

    #[Test]
    public function validateShouldThrowInvalidTokenExceptionWhenResponseBodyMissingTokenKey(): void
    {
        $this->expectException(InvalidTokenException::class);

        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody
            ->method('getContents')
            ->willReturn(json_encode(['data' => 'no-token-key'], JSON_THROW_ON_ERROR));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $this->jwtDecoder->expects($this->never())->method('decode');

        $this->validator->validate('some-token');
    }

    #[Test]
    public function validateShouldThrowInvalidTokenExceptionWhenHttpClientThrows(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(new \RuntimeException('Connection failed'));

        $this->validator->validate('some-token');
    }
}
