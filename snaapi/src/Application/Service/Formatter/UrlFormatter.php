<?php

declare(strict_types=1);

namespace App\Application\Service\Formatter;

/**
 * SRP: Formats and generates URLs.
 *
 * Single responsibility: Only handles URL formatting logic.
 */
final readonly class UrlFormatter implements UrlFormatterInterface
{
    private const TWITTER_BASE_URL = 'https://twitter.com/';

    public function __construct(
        private string $baseUrl = '',
    ) {
    }

    public function formatEditorialUrl(string $url, string $extension): string
    {
        if ('' === $extension) {
            return $url;
        }

        return sprintf('%s.%s', rtrim($url, '/'), $extension);
    }

    public function formatJournalistUrl(string $alias, string $sectionSlug, string $extension): string
    {
        $path = sprintf('/autor/%s/%s', $sectionSlug, $alias);

        if ('' !== $extension) {
            $path .= '.' . $extension;
        }

        return $this->baseUrl . $path;
    }

    public function formatTwitterUrl(?string $twitterHandle): ?string
    {
        if (null === $twitterHandle || '' === trim($twitterHandle)) {
            return null;
        }

        $handle = ltrim($twitterHandle, '@');

        return self::TWITTER_BASE_URL . $handle;
    }
}
