<?php

declare(strict_types=1);

namespace App\Domain\Port\Gateway;

use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Port interface for Multimedia microservice access.
 */
interface MultimediaGatewayInterface
{
    public function findById(string $id): ?Multimedia;

    public function findByIdAsync(string $id): PromiseInterface;

    public function findPhotoById(string $id): ?MultimediaPhoto;

    public function findPhotoByIdAsync(string $id): PromiseInterface;
}
