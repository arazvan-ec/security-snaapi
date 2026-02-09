<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateway\Http;

use App\Domain\Port\Gateway\MembershipGatewayInterface;
use Ec\Membership\Infrastructure\Client\Http\QueryMembershipClient;
use GuzzleHttp\Promise\PromiseInterface;
use Http\Promise\Promise;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * HTTP implementation of MembershipGatewayInterface.
 */
final readonly class MembershipHttpGateway implements MembershipGatewayInterface
{
    private const ASYNC = true;

    public function __construct(
        private QueryMembershipClient $client,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param UriInterface[] $uris
     *
     * @return array<string, string>
     */
    public function getMembershipUrls(string $editorialId, array $uris, string $siteName): array
    {
        if ([] === $uris) {
            return [];
        }

        try {
            /** @var Promise $promise */
            $promise = $this->client->getMembershipUrl($editorialId, $uris, $siteName, self::ASYNC);

            /** @var array<string, string> $result */
            $result = $promise->wait();

            return $result;
        } catch (\Throwable $e) {
            $this->logger->warning('Membership URLs not found', [
                'editorialId' => $editorialId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @param UriInterface[] $uris
     */
    public function getMembershipUrlsAsync(string $editorialId, array $uris, string $siteName): PromiseInterface
    {
        return $this->client->getMembershipUrl($editorialId, $uris, $siteName, self::ASYNC);
    }
}
