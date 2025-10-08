<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

interface ExceptionHandlerInterface
{
    public function supports(\Throwable $exception): bool;

    public function handle(\Throwable $exception, ExceptionEvent $event): JsonResponse;
}
