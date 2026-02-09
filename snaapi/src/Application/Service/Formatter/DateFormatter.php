<?php

declare(strict_types=1);

namespace App\Application\Service\Formatter;

/**
 * SRP: Formats dates to string representations.
 *
 * Single responsibility: Only handles date formatting logic.
 * This class was extracted from EditorialResponseFactory to follow SRP.
 */
final readonly class DateFormatter implements DateFormatterInterface
{
    private const DEFAULT_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private string $format = self::DEFAULT_FORMAT,
    ) {
    }

    public function format(\DateTimeInterface $date): string
    {
        return $date->format($this->format);
    }

    public function formatNullable(?\DateTimeInterface $date): ?string
    {
        if (null === $date) {
            return null;
        }

        return $this->format($date);
    }

    public function toIso8601(\DateTimeInterface $date): string
    {
        return $date->format(\DateTimeInterface::ATOM);
    }
}
