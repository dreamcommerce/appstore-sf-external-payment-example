<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\Payment\PaymentApiException;
use App\Exception\Payment\TemporaryPaymentApiException;
use App\Service\Exception\ApiExceptionClassifier;
use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: 'kernel.exception', priority: 100)]
class ApiExceptionListener
{
    private ApiExceptionClassifier $exceptionClassifier;
    private LoggerInterface $logger;

    public function __construct(
        ApiExceptionClassifier $exceptionClassifier,
        LoggerInterface $logger
    ) {
        $this->exceptionClassifier = $exceptionClassifier;
        $this->logger = $logger;
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (!$this->isApiRequest($request)) {
            return;
        }

        if ($exception instanceof ApiException) {
            $this->handleApiException($exception, null);
        }

        if ($exception instanceof HandlerFailedException) {
            $this->handleHandlerFailedException($exception);
            return;
        }

        $response = match (true) {
            $exception instanceof HttpExceptionInterface => $this->createJsonResponse(
                false,
                $exception->getMessage(),
                $exception->getStatusCode()
            ),

            $exception instanceof ValidationFailedException => $this->handleValidationException($exception),
            $exception instanceof NotEncodableValueException => $this->createJsonResponse(
                false,
                'Invalid JSON format',
                Response::HTTP_BAD_REQUEST
            ),

            default => $this->handleGenericException($exception)
        };

        $event->setResponse($response);
    }

    private function handleApiException(ApiException $exception, ?string $shopCode): void
    {
        $this->logger->error('API error occurred', [
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'shop_code' => $shopCode,
        ]);

        if ($this->exceptionClassifier->isRecoverableError($exception)) {
            throw new TemporaryPaymentApiException(
                'Temporary API error: ' . $exception->getMessage(),
                $exception->getCode(),
                $exception,
            );
        } else {
            throw new PaymentApiException(
                'API error: ' . $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    private function handleHandlerFailedException(HandlerFailedException $exception): void
    {
        $nestedExceptions = $exception->getWrappedExceptions();
        $shopCode = null;
        foreach ($exception->getEnvelope()->all() as $stamp) {
            if (method_exists($stamp, 'getMessage') && method_exists($stamp->getMessage(), 'getShopCode')) {
                $shopCode = $stamp->getMessage()->getShopCode();
                break;
            }
        }

        foreach ($nestedExceptions as $nestedException) {
            if ($nestedException instanceof ApiException) {
                $this->handleApiException($nestedException, $shopCode);
            }
        }
    }

    private function handleValidationException(ValidationFailedException $exception): JsonResponse
    {
        $violations = $exception->getViolations();
        $errors = [];

        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $this->createJsonResponse(
            false,
            'Validation failed',
            Response::HTTP_BAD_REQUEST,
            ['errors' => $errors]
        );
    }

    private function handleGenericException(\Throwable $exception): JsonResponse
    {
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'An unexpected error occurred';

        $this->logger->error('Unhandled exception', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        if ($_ENV['APP_ENV'] === 'dev') {
            $message = $exception->getMessage();
        }

        return $this->createJsonResponse(false, $message, $statusCode);
    }

    private function createJsonResponse(bool $success, string $message, int $statusCode = 200, array $additionalData = []): JsonResponse
    {
        $data = [
            'success' => $success,
            'message' => $message,
        ];

        if (!empty($additionalData)) {
            $data = array_merge($data, $additionalData);
        }

        return new JsonResponse($data, $statusCode);
    }

    private function isApiRequest($request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/')
            || str_contains($request->getPathInfo(), '/app-store/')
            || $request->headers->get('Accept') === 'application/json';
    }
}
