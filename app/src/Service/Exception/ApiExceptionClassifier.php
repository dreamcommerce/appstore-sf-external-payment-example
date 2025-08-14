<?php

namespace App\Service\Exception;

use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;

class ApiExceptionClassifier
{
    private const RECOVERABLE_HTTP_CODES = [
        500, // Internal Server Error
        502, // Bad Gateway
        503, // Service Unavailable
        504, // Gateway Timeout
        429, // Too Many Requests
    ];

    public function isRecoverableError(ApiException $e): bool
    {
        $code = $e->getCode();
        if (in_array($code, self::RECOVERABLE_HTTP_CODES, true)) {
            return true;
        }

        $message = strtolower($e->getMessage());
        if (
            strpos($message, 'timeout') !== false ||
            strpos($message, 'too many requests') !== false ||
            strpos($message, 'try again') !== false ||
            strpos($message, 'temporarily unavailable') !== false
        ) {
            return true;
        }

        return false;
    }
}
