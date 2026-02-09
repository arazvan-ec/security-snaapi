<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateway\Http;

use App\Domain\Port\Gateway\JournalistGatewayInterface;
use Ec\Journalist\Domain\Model\AliasId;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Journalist\Domain\Model\JournalistFactory;
use Ec\Journalist\Domain\Model\QueryJournalistClient;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Log\LoggerInterface;

/**
 * HTTP implementation of JournalistGatewayInterface.
 */
final readonly class JournalistHttpGateway implements JournalistGatewayInterface
{
    private const ASYNC = true;

    public function __construct(
        private QueryJournalistClient $client,
        private JournalistFactory $journalistFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function findByAliasId(string $aliasId): ?Journalist
    {
        try {
            $aliasIdModel = $this->journalistFactory->buildAliasId($aliasId);

            return $this->client->findJournalistByAliasId($aliasIdModel);
        } catch (\Throwable $e) {
            $this->logger->warning('Journalist not found', ['aliasId' => $aliasId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function findByAliasIdAsync(string $aliasId): PromiseInterface
    {
        $aliasIdModel = $this->journalistFactory->buildAliasId($aliasId);

        return $this->client->findJournalistByAliasId($aliasIdModel, self::ASYNC);
    }
}
