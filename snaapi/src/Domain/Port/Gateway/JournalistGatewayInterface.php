<?php

declare(strict_types=1);

namespace App\Domain\Port\Gateway;

use Ec\Journalist\Domain\Model\Journalist;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Port interface for Journalist microservice access.
 */
interface JournalistGatewayInterface
{
    public function findByAliasId(string $aliasId): ?Journalist;

    public function findByAliasIdAsync(string $aliasId): PromiseInterface;
}
