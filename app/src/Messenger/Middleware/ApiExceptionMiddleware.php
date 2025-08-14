<?php

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
        } catch (ApiException $e) {
            $this->handleApiException($envelope, $e);
        } catch (HandlerFailedException $e) {
            $this->handleNestedExceptions($envelope, $e);
        }
    }

    private function handleApiException(Envelope $envelope, ApiException $e): never
    {
        $messageName = get_class($envelope->getMessage());
        $this->logger->error('API error while handling message', [
            'message_class' => $messageName,
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
        ]);

        if ($this->exceptionClassifier->isRecoverableError($e)) {
            throw new TemporaryPaymentApiException(
                'Temporary API error: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        } else {
            throw new PaymentApiException(
                'API error: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    private function handleNestedExceptions(Envelope $envelope, HandlerFailedException $handlerException): never
    {
        $exceptions = $handlerException->getWrappedExceptions();
        foreach ($exceptions as $exception) {
            if ($exception instanceof ApiException) {
                $this->handleApiException($envelope, $exception);
            }
        }

        throw $handlerException;
    }
}
