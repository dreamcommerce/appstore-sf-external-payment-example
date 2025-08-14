<?php

namespace App\EventListener;

use App\Exception\Payment\PaymentApiException;
use App\Exception\Payment\TemporaryPaymentApiException;
use App\Service\Exception\ApiExceptionClassifier;
use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

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

        if ($exception instanceof ApiException) {
            $this->handleApiException($exception, null);
        }

        if ($exception instanceof HandlerFailedException) {
            $this->handleHandlerFailedException($exception);
        }
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
                $shopCode
            );
        } else {
            throw new PaymentApiException(
                'API error: ' . $exception->getMessage(),
                $exception->getCode(),
                $exception,
                $shopCode
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
}
