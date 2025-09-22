<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\Handler\ExceptionHandlerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

#[AsEventListener(event: 'kernel.exception', priority: 200)]
class ApiExceptionListener
{
    private ExceptionHandlerRegistry $handlerRegistry;
    private LoggerInterface $logger;

    public function __construct(
        ExceptionHandlerRegistry $handlerRegistry,
        LoggerInterface $logger
    ) {
        $this->handlerRegistry = $handlerRegistry;
        $this->logger = $logger;
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getThrowable();

        $this->logger->error('Exception caught', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'path' => $request->getPathInfo()
        ]);

        if (!$this->isApiRequest($request)) {
            return;
        }

        $response = $this->handlerRegistry->handleException($exception, $event);
        $event->setResponse($response);
    }

    private function isApiRequest($request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/')
            || str_contains($request->getPathInfo(), '/app-store/')
            || $request->headers->get('Accept') === 'application/json'
            || $request->getRequestFormat() === 'json';
    }
}
