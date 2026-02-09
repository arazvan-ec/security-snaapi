<?php

declare(strict_types=1);

namespace App\Application\Service\Formatter;

use App\Domain\ValueObject\EditorialType;

/**
 * SRP: Maps editorial type IDs to their representations.
 *
 * Single responsibility: Only handles type mapping logic.
 * OCP: Can be extended via configuration without modification.
 */
final readonly class TypeMapper implements TypeMapperInterface
{
    /**
     * @param array<int, string> $additionalTypes
     */
    public function __construct(
        private array $additionalTypes = [],
    ) {
    }

    public function map(int $typeId): EditorialType
    {
        return EditorialType::fromId($typeId);
    }

    public function getName(int $typeId): string
    {
        // Check additional types first (OCP: configurable extension)
        if (isset($this->additionalTypes[$typeId])) {
            return $this->additionalTypes[$typeId];
        }

        return $this->map($typeId)->name();
    }

    public function isSupported(int $typeId): bool
    {
        return 'unknown' !== $this->getName($typeId);
    }
}
