<?php

declare(strict_types=1);

namespace App\Domain\Port\Gateway;

use Ec\Editorial\Domain\Model\NewsBase;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Port interface for Editorial microservice access.
 *
 * Abstracts HTTP client to enable mocking and allow different implementations.
 */
interface EditorialGatewayInterface
{
    public function findById(string $id): ?NewsBase;

    public function findByIdAsync(string $id): PromiseInterface;
}
