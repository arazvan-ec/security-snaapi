<?php

declare(strict_types=1);

namespace App\Domain\Port\Gateway;

use Ec\Section\Domain\Model\Section;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Port interface for Section microservice access.
 */
interface SectionGatewayInterface
{
    public function findById(string $id): ?Section;

    public function findByIdAsync(string $id): PromiseInterface;
}
