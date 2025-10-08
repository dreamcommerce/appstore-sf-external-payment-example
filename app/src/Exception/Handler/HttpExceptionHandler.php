<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class HttpExceptionHandler extends AbstractExceptionHandler
{
    public function __construct(LoggerInterface $logger, string $environment)
    {
        parent::__construct($logger, $environment);
    }

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof HttpExceptionInterface;
    }

    public function handle(\Throwable $exception, ExceptionEvent $event): JsonResponse
    {
        /** @var HttpExceptionInterface $exception */
        $statusCode = $exception->getStatusCode();
        $message = $exception->getMessage();

        $this->logger->notice('HTTP exception', [
            'status_code' => $statusCode,
            'message' => $message,
            'path' => $event->getRequest()->getPathInfo()
        ]);

        return $this->createJsonResponse(
            false,
            $message,
            $statusCode
        );
    }
}
