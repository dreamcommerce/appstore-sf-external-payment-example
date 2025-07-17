<?php

namespace App\Service\Common;

use Psr\Log\LoggerInterface;

trait ExceptionLoggingTrait
{
    private function logException(LoggerInterface $logger, \Throwable $e, string $action, array $context = []): void
    {
        $logger->error("Error during {$action}", array_merge($context, [
            'error_message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]));
    }
}

