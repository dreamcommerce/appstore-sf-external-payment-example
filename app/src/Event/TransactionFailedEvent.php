<?php

declare(strict_types=1);

namespace App\Event\Transaction;

/**
 * Event dispatched when a transaction processing fails
 */
class TransactionFailedEvent
{
    public function __construct(
        private readonly string $transactionId,
        private readonly string $reason,
        private readonly array $context = []
    ) {
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
