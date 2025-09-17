<?php

declare(strict_types=1);

namespace App\Event\Transaction;

use App\Entity\Transaction;

/**
 * Event dispatched when a transaction is successfully created
 */
class TransactionCreatedEvent
{
    public function __construct(
        private readonly Transaction $transaction,
        private readonly array $context = []
    ) {
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
