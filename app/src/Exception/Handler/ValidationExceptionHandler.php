<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ValidationExceptionHandler extends AbstractExceptionHandler
{
    public function __construct(LoggerInterface $logger, string $environment)
    {
        parent::__construct($logger, $environment);
    }

    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof ValidationFailedException;
    }

    public function handle(\Throwable $exception, ExceptionEvent $event): JsonResponse
    {
        /** @var ValidationFailedException $exception */
        $violations = $exception->getViolations();
        $errors = [];

        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        $this->logger->notice('Validation failed', [
            'errors' => $errors,
            'path' => $event->getRequest()->getPathInfo()
        ]);

        return $this->createJsonResponse(
            false,
            'Validation failed',
            Response::HTTP_BAD_REQUEST,
            ['errors' => $errors]
        );
    }
}
