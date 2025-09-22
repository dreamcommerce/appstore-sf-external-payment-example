<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class MessengerExceptionHandler extends AbstractExceptionHandler
{
    private ApiExceptionHandler $apiExceptionHandler;

    public function __construct(
        LoggerInterface $logger,
        ApiExceptionHandler $apiExceptionHandler,
        string $environment
    ) {
        parent::__construct($logger, $environment);
        $this->apiExceptionHandler = $apiExceptionHandler;
    }

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof HandlerFailedException;
    }

    public function handle(\Throwable $exception, ExceptionEvent $event): JsonResponse
    {
        /** @var HandlerFailedException $exception */
        $nestedExceptions = $exception->getWrappedExceptions();
        $shopCode = null;

        foreach ($exception->getEnvelope()->all() as $stamp) {
            if (method_exists($stamp, 'getMessage') && method_exists($stamp->getMessage(), 'getShopCode')) {
                $shopCode = $stamp->getMessage()->getShopCode();
                $event->getRequest()->attributes->set('shopCode', $shopCode);
                break;
            }
        }

        foreach ($nestedExceptions as $nestedException) {
            if ($nestedException instanceof ApiException) {
                return $this->apiExceptionHandler->handle($nestedException, $event);
            }
        }

        $nestedException = $nestedExceptions[0] ?? $exception;
        $this->logger->error('Messenger exception', [
            'exception' => get_class($nestedException),
            'message' => $nestedException->getMessage(),
            'code' => $nestedException->getCode(),
            'shop_code' => $shopCode,
            'path' => $event->getRequest()->getPathInfo()
        ]);

        $message = $this->isDevelopmentEnvironment()
            ? $nestedException->getMessage()
            : 'An error occurred while processing your request';

        return $this->createJsonResponse(false, $message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
