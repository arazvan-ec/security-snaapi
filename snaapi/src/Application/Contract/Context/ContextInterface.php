<?php

declare(strict_types=1);

namespace App\Application\Contract\Context;

/**
 * Base context interface for all pipeline contexts.
 *
 * Combines all segregated interfaces following ISP.
 * Clients can depend on specific interfaces they need instead of this full interface.
 */
interface ContextInterface extends
    HasEditorialInterface,
    HasSectionInterface,
    HasMultimediaInterface,
    HasTagsInterface,
    HasJournalistsInterface,
    HasCommentsInterface,
    HasMembershipInterface
{
    public function editorialId(): string;

    /**
     * Generic getter for additional context data.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if context has a specific key.
     */
    public function has(string $key): bool;
}
