<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Transaction;

interface TransactionRepositoryInterface
{
    /**
     * Save transaction entity
     */
    public function save(Transaction $transaction, bool $flush = true): void;

    /**
     * Finds one Transaction by its external transaction ID.
     */
    public function findOneByExternalTransactionId(string $transactionId): ?Transaction;
}
