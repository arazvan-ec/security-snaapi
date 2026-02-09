<?php

declare(strict_types=1);

namespace App\Domain\Port\Gateway;

/**
 * Port interface for comment service access.
 *
 * Abstracts HTTP client to enable mocking and allow different implementations.
 */
interface CommentGatewayInterface
{
    public function findCommentCountByEditorialId(string $editorialId): int;
}
