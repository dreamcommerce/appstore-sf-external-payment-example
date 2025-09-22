<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ExceptionHandlerRegistry
{
    /**
     * @var ExceptionHandlerInterface[]
     */
    private iterable $handlers;
    private LoggerInterface $logger;

    public function __construct(iterable $handlers, LoggerInterface $logger)
    {
        $this->handlers = $handlers;
        $this->logger = $logger;
    }

    public function handleException(\Throwable $exception, ExceptionEvent $event): JsonResponse
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($exception)) {
                return $handler->handle($exception, $event);
            }
        }

        $this->logger->warning('No exception handler found for exception', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage()
        ]);

        return new JsonResponse([
            'success' => false,
            'message' => 'An unexpected error occurred'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
