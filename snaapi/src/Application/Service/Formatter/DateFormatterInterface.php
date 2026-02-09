<?php

declare(strict_types=1);

namespace App\Application\Service\Formatter;

/**
 * SRP: Interface for date formatting.
 *
 * Single responsibility: Convert dates to string representations.
 * DIP: High-level modules depend on this abstraction.
 */
interface DateFormatterInterface
{
    public function format(\DateTimeInterface $date): string;

    public function formatNullable(?\DateTimeInterface $date): ?string;

    public function toIso8601(\DateTimeInterface $date): string;
}
