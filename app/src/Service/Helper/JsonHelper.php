<?php

declare(strict_types=1);

namespace App\Service\Helper;

class JsonHelper
{
    /**
     * Decode a JSON string into an array with error handling
     */
    public function decodeToArray(?string $jsonString, array $defaultOnError = []): array
    {
        if ($jsonString === null) {
            return $defaultOnError;
        }

        try {
            $data = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : $defaultOnError;
        } catch (\JsonException) {
            return $defaultOnError;
        }
    }
}
