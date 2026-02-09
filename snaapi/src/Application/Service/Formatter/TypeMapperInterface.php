<?php

declare(strict_types=1);

namespace App\Application\Service\Formatter;

use App\Domain\ValueObject\EditorialType;

/**
 * SRP: Interface for editorial type mapping.
 *
 * Single responsibility: Map editorial type IDs to their representations.
 * OCP: New types can be added without modifying existing code.
 */
interface TypeMapperInterface
{
    public function map(int $typeId): EditorialType;

    public function getName(int $typeId): string;

    public function isSupported(int $typeId): bool;
}
