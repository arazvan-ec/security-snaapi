<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateway\Http;

use App\Domain\Port\Gateway\SectionGatewayInterface;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * HTTP implementation of SectionGatewayInterface.
 */
final readonly class SectionHttpGateway implements SectionGatewayInterface
{
    private const ASYNC = true;

    public function __construct(
        private QuerySectionClient $client,
    ) {
    }

    public function findById(string $id): ?Section
    {
        try {
            return $this->client->findSectionById($id);
        } catch (\Throwable) {
            return null;
        }
    }

    public function findByIdAsync(string $id): PromiseInterface
    {
        return $this->client->findSectionById($id, self::ASYNC);
    }
}
