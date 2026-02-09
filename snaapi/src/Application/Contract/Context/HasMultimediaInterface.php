<?php

declare(strict_types=1);

namespace App\Application\Contract\Context;

use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;

/**
 * ISP: Interface for contexts that provide multimedia data.
 *
 * Segregated interface following Interface Segregation Principle.
 */
interface HasMultimediaInterface
{
    public function multimedia(): ?Multimedia;

    public function hasMultimedia(): bool;

    /**
     * @return array<string, mixed>
     */
    public function multimediaOpening(): array;

    /**
     * @return array<string, mixed>
     */
    public function bodyPhotos(): array;
}
