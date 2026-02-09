<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateway\Http;

use App\Domain\Port\Gateway\MultimediaGatewayInterface;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Infrastructure\Client\Http\QueryMultimediaClient;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * HTTP implementation of MultimediaGatewayInterface.
 */
final readonly class MultimediaHttpGateway implements MultimediaGatewayInterface
{
    private const ASYNC = true;

    public function __construct(
        private QueryMultimediaClient $client,
    ) {
    }

    public function findById(string $id): ?Multimedia
    {
        try {
            return $this->client->findMultimediaById($id);
        } catch (\Throwable) {
            return null;
        }
    }

    public function findByIdAsync(string $id): PromiseInterface
    {
        return $this->client->findMultimediaById($id, self::ASYNC);
    }

    public function findPhotoById(string $id): ?MultimediaPhoto
    {
        try {
            $photo = $this->client->findPhotoById($id);

            return $photo instanceof MultimediaPhoto ? $photo : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function findPhotoByIdAsync(string $id): PromiseInterface
    {
        return $this->client->findPhotoById($id, self::ASYNC);
    }
}
