<?php

namespace App\Messenger\Serializer;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Custom serializer that limits exception details when messages fail
 */
class ExceptionContextMinimalSerializer implements SerializerInterface
{
    private PhpSerializer $phpSerializer;
    private int $maxBodySize;

    public function __construct(int $maxBodySize = 10000)
    {
        $this->phpSerializer = new PhpSerializer();
        $this->maxBodySize = $maxBodySize;
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        try {
            return $this->phpSerializer->decode($encodedEnvelope);
        } catch (MessageDecodingFailedException $e) {
            throw new MessageDecodingFailedException(
                'Failed to decode message: ' . substr($e->getMessage(), 0, 200) . '...',
                [],
                $e
            );
        }
    }

    public function encode(Envelope $envelope): array
    {
        $envelope = $this->cleanAllStamps($envelope);
        $encoded = $this->phpSerializer->encode($envelope);

        if (isset($encoded['body']) && strlen($encoded['body']) > $this->maxBodySize) {
            $encoded['body'] = substr($encoded['body'], 0, $this->maxBodySize) .
                '... [truncated, original size: ' . strlen($encoded['body']) . ' bytes]';
        }

        return $encoded;
    }

    private function cleanAllStamps(Envelope $envelope): Envelope
    {
        $envelope = $this->cleanRedeliveryStamp($envelope);
        $envelope = $this->cleanErrorDetailsStamp($envelope);
        return $this->removeOtherStamps($envelope);
    }

    private function cleanRedeliveryStamp(Envelope $envelope): Envelope
    {
        /** @var RedeliveryStamp|null $redeliveryStamp */
        $redeliveryStamp = $envelope->last(RedeliveryStamp::class);
        if ($redeliveryStamp === null) {
            return $envelope;
        }

        $envelope = $envelope->withoutStampsOfType(RedeliveryStamp::class);
        return $envelope->with(new RedeliveryStamp($redeliveryStamp->getRetryCount()));
    }

    private function cleanErrorDetailsStamp(Envelope $envelope): Envelope
    {
        /** @var ErrorDetailsStamp|null $errorDetailsStamp */
        $errorDetailsStamp = $envelope->last(ErrorDetailsStamp::class);
        if ($errorDetailsStamp === null) {
            return $envelope;
        }

        $envelope = $envelope->withoutStampsOfType(ErrorDetailsStamp::class);
        $message = $errorDetailsStamp->getExceptionMessage();
        if (strlen($message) > 200) {
            $message = substr($message, 0, 200) . '...';
        }

        return $envelope->with(new ErrorDetailsStamp(
            $message,
            $errorDetailsStamp->getExceptionCode(),
            $errorDetailsStamp->getExceptionClass()
        ));
    }

    private function removeOtherStamps(Envelope $envelope): Envelope
    {
        $keepStampTypes = [
            RedeliveryStamp::class,
            ErrorDetailsStamp::class,
        ];

        $stamps = $envelope->all();
        $newEnvelope = new Envelope($envelope->getMessage());

        foreach ($keepStampTypes as $type) {
            if (isset($stamps[$type])) {
                foreach ($stamps[$type] as $stamp) {
                    $newEnvelope = $newEnvelope->with($stamp);
                }
            }
        }

        return $newEnvelope;
    }
}
