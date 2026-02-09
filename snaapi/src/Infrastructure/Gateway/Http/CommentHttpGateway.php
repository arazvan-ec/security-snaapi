<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateway\Http;

use App\Domain\Port\Gateway\CommentGatewayInterface;
use App\Ec\Snaapi\Infrastructure\Client\Http\QueryLegacyClient;

/**
 * HTTP implementation of CommentGatewayInterface.
 *
 * Wraps the QueryLegacyClient to abstract HTTP communication.
 */
final readonly class CommentHttpGateway implements CommentGatewayInterface
{
    public function __construct(
        private QueryLegacyClient $client,
    ) {
    }

    public function findCommentCountByEditorialId(string $editorialId): int
    {
        try {
            /** @var array{options: array{totalrecords?: int}} $comments */
            $comments = $this->client->findCommentsByEditorialId($editorialId);

            return $comments['options']['totalrecords'] ?? 0;
        } catch (\Throwable) {
            return 0;
        }
    }
}
