<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateway\Http;

use App\Domain\Port\Gateway\EditorialGatewayInterface;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * HTTP implementation of EditorialGatewayInterface.
 *
 * Wraps the QueryEditorialClient to abstract HTTP communication.
 */
final readonly class EditorialHttpGateway implements EditorialGatewayInterface
{
    private const ASYNC = true;

    public function __construct(
        private QueryEditorialClient $client,
    ) {
    }

    public function findById(string $id): ?NewsBase
    {
        try {
            return $this->client->findEditorialById($id);
        } catch (\Throwable) {
            return null;
        }
    }

    public function findByIdAsync(string $id): PromiseInterface
    {
        return $this->client->findEditorialById($id, self::ASYNC);
    }
}
