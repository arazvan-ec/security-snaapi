<?php

declare(strict_types=1);

namespace App\Application\Service\Formatter;

/**
 * SRP: Interface for URL formatting and generation.
 *
 * Single responsibility: Handle URL formatting logic.
 */
interface UrlFormatterInterface
{
    public function formatEditorialUrl(string $url, string $extension): string;

    public function formatJournalistUrl(string $alias, string $sectionSlug, string $extension): string;

    public function formatTwitterUrl(?string $twitterHandle): ?string;
}
