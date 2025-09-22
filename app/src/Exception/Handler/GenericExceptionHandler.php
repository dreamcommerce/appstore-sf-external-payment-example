<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class GenericExceptionHandler extends AbstractExceptionHandler
{
    public function __construct(LoggerInterface $logger, string $environment)
    {
        parent::__construct($logger, $environment);
    }

    public function supports(\Throwable $exception): bool
    {
        return true;
    }

    public function handle(\Throwable $exception, ExceptionEvent $event): JsonResponse
    {
        $this->logger->error('Unhandled exception', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'path' => $event->getRequest()->getPathInfo()
        ]);

        $message = $this->isDevelopmentEnvironment()
            ? $exception->getMessage()
            : 'An unexpected error occurred';

        return $this->createJsonResponse(
            false,
            $message,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
