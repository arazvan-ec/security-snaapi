<?php

declare(strict_types=1);

namespace App\Domain\Port\Gateway;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Port interface for Membership microservice access.
 */
interface MembershipGatewayInterface
{
    /**
     * @param UriInterface[] $uris
     *
     * @return array<string, string>
     */
    public function getMembershipUrls(string $editorialId, array $uris, string $siteName): array;

    /**
     * @param UriInterface[] $uris
     */
    public function getMembershipUrlsAsync(string $editorialId, array $uris, string $siteName): PromiseInterface;
}
