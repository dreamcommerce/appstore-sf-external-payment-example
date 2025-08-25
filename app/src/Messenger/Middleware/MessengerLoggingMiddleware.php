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

    private function serializeMessage(object $message): array
    {
        $reflection = new \ReflectionObject($message);
        $data = [];
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $data[$property->getName()] = $property->getValue($message);
        }
        return $data;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $messageClass = get_class($message);
        $messageId = null;
        if (property_exists($message, 'id')) {
            $messageId = $message->id;
        } elseif (method_exists($message, 'getId')) {
            $messageId = $message->getId();
        } else {
            $messageId = spl_object_hash($message);
        }

        if ($message instanceof \JsonSerializable) {
            $serialized = json_encode($message->jsonSerialize());
        } elseif (method_exists($message, 'toArray')) {
            $serialized = json_encode($message->toArray());
        } else {
            $data = get_object_vars($message);
            if (empty($data)) {
                $data = $this->serializeMessage($message);
            }
            $serialized = json_encode($data);
        }
        $this->logger->info('Messenger message handled', [
            'messageId' => $messageId,
            'type' => $messageClass,
            'data' => $serialized
        ]);
        return $stack->next()->handle($envelope, $stack);
    }
}
