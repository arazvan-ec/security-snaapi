<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateway\Http;

use App\Domain\Port\Gateway\TagGatewayInterface;
use Ec\Tag\Domain\Model\QueryTagClient;
use Ec\Tag\Domain\Model\Tag;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Log\LoggerInterface;

/**
 * HTTP implementation of TagGatewayInterface.
 */
final readonly class TagHttpGateway implements TagGatewayInterface
{
    private const ASYNC = true;

    public function __construct(
        private QueryTagClient $client,
        private LoggerInterface $logger,
    ) {
    }

    public function findById(string $id): ?Tag
    {
        try {
            return $this->client->findTagById($id);
        } catch (\Throwable $e) {
            $this->logger->warning('Tag not found', ['id' => $id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function findByIdAsync(string $id): PromiseInterface
    {
        return $this->client->findTagById($id, self::ASYNC);
    }

    /**
     * @param string[] $ids
     *
     * @return Tag[]
     */
    public function findByIds(array $ids): array
    {
        $tags = [];
        foreach ($ids as $id) {
            $tag = $this->findById($id);
            if (null !== $tag) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }
}
