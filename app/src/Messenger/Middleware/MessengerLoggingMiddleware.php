<?php

declare(strict_types=1);

namespace App\Messenger\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;

class MessengerLoggingMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $this->logger->info('Processing message', [
            'type' => get_class($message),
            'data' => $this->getMessageData($message)
        ]);

        return $stack->next()->handle($envelope, $stack);
    }

    private function getMessageData(object $message): array
    {
        if (method_exists($message, 'toArray')) {
            return $message->toArray();
        }

        if ($message instanceof \JsonSerializable) {
            return $message->jsonSerialize();
        }

        return get_object_vars($message);
    }
}
