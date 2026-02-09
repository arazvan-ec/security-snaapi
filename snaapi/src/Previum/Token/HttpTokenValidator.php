<?php

declare(strict_types=1);

namespace App\Previum\Token;

use App\Previum\Exception\InvalidTokenException;
use Http\Client\HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class HttpTokenValidator implements TokenValidatorInterface
{
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly RequestFactoryInterface&StreamFactoryInterface $messageFactory,
        private readonly JwtDecoderInterface $jwtDecoder,
        private readonly string $host,
        private readonly string $endpoint,
    ) {
    }

    /** @return array<string, mixed> */
    public function validate(string $token): array
    {
        try {
            $body = $this->messageFactory->createStream(json_encode(['token' => $token], JSON_THROW_ON_ERROR));

            $request = $this->messageFactory
                ->createRequest('POST', $this->host . $this->endpoint)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($body);

            $response = $this->httpClient->sendRequest($request);

            if ($response->getStatusCode() >= 400) {
                throw new InvalidTokenException();
            }

            $responseBody = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($responseBody) || !isset($responseBody['token'])) {
                throw new InvalidTokenException();
            }

            /** @var string $responseJwt */
            $responseJwt = $responseBody['token'];

            return $this->jwtDecoder->decode($responseJwt);
        } catch (InvalidTokenException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new InvalidTokenException('Unauthorized', 401, $e);
        }
    }
}
