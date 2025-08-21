<?php

namespace App\EventListener;

use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;
use DreamCommerce\Component\ShopAppstore\Api\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Global exception listener for handling all application exceptions
 * including PHP errors/warnings that have been converted to ErrorException.
 */
class ExceptionListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $this->logger->error('Exception caught by global listener', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => implode("\n", array_map(
                fn($t) => "{$t['file']}:{$t['line']} {$t['function']}",
                array_slice($exception->getTrace(), 0, 10)
            )),
            'controller' => $event->getRequest()->attributes->get('_controller'),
            'route' => $event->getRequest()->attributes->get('_route')
        ]);

        if ($exception instanceof HandlerFailedException) {
            $exception = $this->unwrapHandlerFailedException($exception);
        }

        if ($this->isApiRequest($event->getRequest())) {
            $response = $this->createErrorResponse($exception);
            $event->setResponse($response);
        }
    }

    private function unwrapHandlerFailedException(HandlerFailedException $exception): \Throwable
    {
        $wrappedExceptions = $exception->getWrappedExceptions();
        $nestedException = $wrappedExceptions[0] ?? $exception;

        $context = [
            'exception' => get_class($nestedException),
            'message' => $nestedException->getMessage(),
            'code' => $nestedException->getCode(),
            'file' => $nestedException->getFile(),
            'line' => $nestedException->getLine(),
        ];

        if ($nestedException instanceof ApiException && method_exists($nestedException, 'getHttpResponse')) {
            if ($response = $nestedException->getHttpResponse()) {
                $body = (string)$response->getBody();
                $context['response_body'] = $body;
                $response->getBody()->rewind();
            }
        }

        $this->logger->error('Unwrapped exception from message handler', $context);

        return $nestedException;

    }

    private function createErrorResponse(\Throwable $exception): JsonResponse
    {
        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $message = (($_ENV['APP_ENV'] ?? 'prod') === 'dev')
            ? $exception->getMessage()
            : 'An error occurred';

        $data = [
            'success' => false,
            'message' => $message
        ];

        if ($exception instanceof ValidationFailedException) {
            $errors = [];
            foreach ($exception->getViolations() as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            $data['errors'] = $errors;
            $statusCode = Response::HTTP_BAD_REQUEST;
        } elseif ($exception instanceof ValidationException) {
            $errors = [];
            if (method_exists($exception, 'getHttpResponse') && $response = $exception->getHttpResponse()) {
                $body = (string) $response->getBody();
                $response->getBody()->rewind();
                $responseData = json_decode($body, true);
                if (isset($responseData['errors']) && is_array($responseData['errors'])) {
                    $errors = $responseData['errors'];
                } else {
                    $errors['body'] = $body;
                }
            }
            $data['errors'] = $errors;
            $statusCode = Response::HTTP_BAD_REQUEST;
        }

        return new JsonResponse($data, $statusCode);
    }

    private function isApiRequest($request): bool
    {
        return $request->getRequestFormat() === 'json'
            || $request->getContentTypeFormat() === 'json'
            || strpos($request->headers->get('Accept', ''), 'application/json') !== false;
    }
}
