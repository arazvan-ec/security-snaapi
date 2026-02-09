<?php

declare(strict_types=1);

namespace App\Domain\Port\Gateway;

use Ec\Tag\Domain\Model\Tag;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Port interface for Tag microservice access.
 */
interface TagGatewayInterface
{
    public function findById(string $id): ?Tag;

    public function findByIdAsync(string $id): PromiseInterface;

    /**
     * @param string[] $ids
     *
     * @return Tag[]
     */
    public function findByIds(array $ids): array;
}
