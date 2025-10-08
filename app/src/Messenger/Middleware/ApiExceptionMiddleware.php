<?php

declare(strict_types=1);

namespace App\Messenger\Middleware;

use App\Exception\Payment\PaymentApiException;
use App\Exception\Payment\TemporaryPaymentApiException;
use App\Service\Exception\ApiExceptionClassifier;
use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class ApiExceptionMiddleware implements MiddlewareInterface
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

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (ApiException $exception) {
            $this->handleApiException($envelope, $exception);
        } catch (HandlerFailedException $exception) {
            $this->handleHandlerFailedException($envelope, $exception);
        }
    }

    private function handleApiException(Envelope $envelope, ApiException $exception): never
    {
        $this->logger->error('API error', [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'message_type' => get_class($envelope->getMessage())
        ]);

        if ($this->exceptionClassifier->isRecoverableError($exception)) {
            throw new TemporaryPaymentApiException(
                'Temporary API error: ' . $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        throw new PaymentApiException(
            'API error: ' . $exception->getMessage(),
            $exception->getCode(),
            $exception
        );
    }

    private function handleHandlerFailedException(Envelope $envelope, HandlerFailedException $exception): never
    {
        foreach ($exception->getWrappedExceptions() as $wrappedException) {
            if ($wrappedException instanceof ApiException) {
                $this->handleApiException($envelope, $wrappedException);
            }
        }

        throw $exception;
    }
}
