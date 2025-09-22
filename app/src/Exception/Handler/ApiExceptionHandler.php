<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\Service\Exception\ApiExceptionClassifier;
use DreamCommerce\Component\ShopAppstore\Api\Exception\ApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ApiExceptionHandler extends AbstractExceptionHandler
{
    private ApiExceptionClassifier $exceptionClassifier;

    public function __construct(
        LoggerInterface $logger,
        ApiExceptionClassifier $exceptionClassifier,
        string $environment
    ) {
        parent::__construct($logger, $environment);
        $this->exceptionClassifier = $exceptionClassifier;
    }

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof ApiException;
    }

    public function handle(\Throwable $exception, ExceptionEvent $event): JsonResponse
    {
        /** @var ApiException $exception */
        $shopCode = $event->getRequest()->attributes->get('shopCode');

        $this->logger->error('API error', [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'shop_code' => $shopCode,
            'path' => $event->getRequest()->getPathInfo()
        ]);

        $message = $this->isDevelopmentEnvironment()
            ? $exception->getMessage()
            : 'An error occurred while processing your request';

        return $this->createJsonResponse(
            false,
            $message,
            Response::HTTP_SERVICE_UNAVAILABLE
        );
    }
}
