<?php

declare(strict_types=1);

namespace App\Service\Exception;

use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;
use Symfony\Component\HttpFoundation\Response;

class ApiExceptionClassifier
{
    private const RECOVERABLE_HTTP_CODES = [
        Response::HTTP_INTERNAL_SERVER_ERROR,
        Response::HTTP_BAD_GATEWAY,
        Response::HTTP_SERVICE_UNAVAILABLE,
        Response::HTTP_GATEWAY_TIMEOUT,
        Response::HTTP_TOO_MANY_REQUESTS,
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
