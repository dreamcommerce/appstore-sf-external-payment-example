<?php

declare(strict_types=1);

namespace App\Service\Helper;

class DateTimeHelper
{
    /**
     * Creates a DateTimeImmutable from a string or returns current date if null/invalid
     *
     * @param string|null $dateString Date string in format "YYYY-MM-DD hh:mm:ss"
     */
    public function createFromString(?string $dateString): \DateTimeImmutable
    {
        if ($dateString === null) {
            return new \DateTimeImmutable();
        }

        try {
            return new \DateTimeImmutable($dateString);
        } catch (\Exception) {
            return new \DateTimeImmutable();
        }
    }
}
