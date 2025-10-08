<?php

declare(strict_types=1);

namespace App\EventListener\Transaction;

use App\Entity\Transaction;
use App\Event\Transaction\TransactionCreatedEvent;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Listener responsible for updating related refund information when a transaction is created
 */
#[AsEventListener(event: TransactionCreatedEvent::class)]
final class TransactionRefundUpdateListener
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TransactionRepository $transactionRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(TransactionCreatedEvent $event): void
    {
        $transaction = $event->getTransaction();
        $context = $event->getContext();

        if (!$this->isRefundTransaction($transaction)) {
            return;
        }

        $originalTransactionId = $this->getOriginalTransactionId($transaction, $context);
        if (!$originalTransactionId) {
            $this->logger->warning('Refund transaction created without reference to original transaction', [
                'transaction_id' => $transaction->getId(),
                'context' => $context,
            ]);
            return;
        }

        try {
            $this->updateOriginalTransaction($originalTransactionId, $transaction);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update original transaction with refund information', [
                'original_transaction_id' => $originalTransactionId,
                'refund_transaction_id' => $transaction->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determines if the transaction is a refund based on type or other indicators
     */
    private function isRefundTransaction(Transaction $transaction): bool
    {
        // Implement according to your domain logic
        // For example:
        return $transaction->getType() === 'refund' ||
               (isset($transaction->getMetadata()['is_refund']) && $transaction->getMetadata()['is_refund'] === true);
    }

    /**
     * Extracts the original transaction ID from transaction data or context
     *
     * @param Transaction $transaction The refund transaction
     * @param array $context Additional context provided with the event
     * @return string|null The original transaction ID or null if not found
     */
    private function getOriginalTransactionId(Transaction $transaction, array $context): ?string
    {
        $metadata = $transaction->getMetadata();
        if (isset($metadata['original_transaction_id'])) {
            return $metadata['original_transaction_id'];
        }

        if (isset($context['original_transaction_id'])) {
            return $context['original_transaction_id'];
        }

        return null;
    }

    /**
     * Updates the original transaction with refund information
     *
     * @param string $originalTransactionId ID of the original transaction
     * @param Transaction $refundTransaction The refund transaction
     */
    private function updateOriginalTransaction(string $originalTransactionId, Transaction $refundTransaction): void
    {
        $originalTransaction = $this->transactionRepository->find($originalTransactionId);
        if (!$originalTransaction) {
            $this->logger->warning('Original transaction not found for refund update', [
                'original_transaction_id' => $originalTransactionId,
                'refund_transaction_id' => $refundTransaction->getId(),
            ]);
            return;
        }

        $originalTransaction->setRefundStatus('refunded');

        if (method_exists($originalTransaction, 'addRefundTransaction')) {
            $originalTransaction->addRefundTransaction($refundTransaction);
        }

        if (method_exists($originalTransaction, 'addRefundedAmount')) {
            $originalTransaction->addRefundedAmount($refundTransaction->getAmount());
        }

        $metadata = $originalTransaction->getMetadata() ?? [];
        $metadata['refunded_at'] = (new \DateTime())->format('c');
        $metadata['refund_transaction_id'] = $refundTransaction->getId();
        $originalTransaction->setMetadata($metadata);

        $this->entityManager->flush();

        $this->logger->info('Original transaction updated with refund information', [
            'original_transaction_id' => $originalTransaction->getId(),
            'refund_transaction_id' => $refundTransaction->getId(),
        ]);
    }
}
