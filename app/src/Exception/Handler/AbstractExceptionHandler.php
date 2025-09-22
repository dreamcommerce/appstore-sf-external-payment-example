<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class AbstractExceptionHandler implements ExceptionHandlerInterface
{
    protected LoggerInterface $logger;
    protected string $environment;

    public function __construct(LoggerInterface $logger, string $environment)
    {
        $this->logger = $logger;
        $this->environment = $environment;
    }

    protected function createJsonResponse(
        bool $success,
        string $message,
        int $statusCode = 200,
        array $additionalData = []
    ): JsonResponse {
        $data = [
            'success' => $success,
            'message' => $message,
        ];

        if (!empty($additionalData)) {
            $data = array_merge($data, $additionalData);
        }

        return new JsonResponse($data, $statusCode);
    }

    protected function isDevelopmentEnvironment(): bool
    {
        return $this->environment === 'dev';
    }
}
