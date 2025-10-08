<?php

declare(strict_types=1);

namespace App\EventListener\Transaction;

use App\Event\Transaction\TransactionFailedEvent;
use App\Repository\TransactionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Listener responsible for logging transaction failures and updating related transactions
 */
#[AsEventListener(event: TransactionFailedEvent::class)]
final class TransactionFailureLogListener
{
    public function __construct(
        private readonly TransactionRepository $transactionRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(TransactionFailedEvent $event): void
    {
        $transactionId = $event->getTransactionId();
        $reason = $event->getReason();
        $context = $event->getContext();

        // Enhanced logging with detailed context
        $this->logger->error('Transaction processing failed', [
            'transaction_id' => $transactionId,
            'reason' => $reason,
            'context' => $context,
        ]);

        // Check if this is a refund failure
        if ($this->isRefundFailure($context)) {
            $this->handleRefundFailure($transactionId, $reason, $context);
        }
    }

    /**
     * Determines if the failure is related to a refund operation
     */
    private function isRefundFailure(array $context): bool
    {
        // Implement based on your domain logic
        // For example:
        return
            (isset($context['transaction_type']) && $context['transaction_type'] === 'refund') ||
            (isset($context['is_refund']) && $context['is_refund'] === true) ||
            (isset($context['operation']) && $context['operation'] === 'refund');
    }

    /**
     * Handles refund failure by updating the original transaction status
     */
    private function handleRefundFailure(string $transactionId, string $reason, array $context): void
    {
        $originalTransactionId = $context['original_transaction_id'] ?? null;
        if (!$originalTransactionId) {
            $this->logger->warning('Refund failure without reference to original transaction', [
                'failed_transaction_id' => $transactionId,
            ]);
            return;
        }

        try {
            $originalTransaction = $this->transactionRepository->find($originalTransactionId);
            if (!$originalTransaction) {
                $this->logger->warning('Original transaction not found for failed refund', [
                    'original_transaction_id' => $originalTransactionId,
                    'failed_transaction_id' => $transactionId,
                ]);
                return;
            }

            $metadata = $originalTransaction->getMetadata() ?? [];
            $metadata['refund_failure'] = [
                'timestamp' => (new \DateTime())->format('c'),
                'reason' => $reason,
                'failed_transaction_id' => $transactionId,
            ];
            $originalTransaction->setMetadata($metadata);

            if (method_exists($originalTransaction, 'setRefundStatus')) {
                $originalTransaction->setRefundStatus('refund_failed');

                $this->logger->info('Original transaction updated with refund failure status', [
                    'original_transaction_id' => $originalTransaction->getId(),
                    'failed_refund_id' => $transactionId,
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to update original transaction after refund failure', [
                'original_transaction_id' => $originalTransactionId,
                'failed_transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
