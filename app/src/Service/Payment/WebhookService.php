<?php

declare(strict_types=1);

namespace App\Service\Payment;

use App\Dto\Webhook\OrderRefundWebhookDto;
use App\Dto\Webhook\OrderTransactionWebhookDto;
use App\Event\Transaction\TransactionCreatedEvent;
use App\Event\Transaction\TransactionFailedEvent;
use App\Factory\TransactionFactory;
use App\Repository\ShopAppInstallationRepositoryInterface;
use App\Repository\ShopPaymentMethodRepositoryInterface;
use App\Repository\TransactionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Service responsible for processing payment webhooks
 */
class WebhookService implements WebhookServiceInterface
{
    public function __construct(
        private readonly ShopPaymentMethodRepositoryInterface $shopPaymentMethodRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly ShopAppInstallationRepositoryInterface $shopAppInstallationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TransactionFactory $transactionFactory
    ) {
    }

    public function processOrderTransactionWebhook(OrderTransactionWebhookDto $webhookDto, string $shopLicense): void
    {
        $context = [
            'payment_id' => $webhookDto->paymentId,
            'transaction_id' => $webhookDto->transactionId,
            'order_id' => $webhookDto->orderId,
            'shop_license' => $shopLicense
        ];

        $this->logger->info('Processing order transaction webhook', $context);
        if ($this->transactionExists($webhookDto->transactionId)) {
            return;
        }

        $shop = $this->shopAppInstallationRepository->findOneByShopLicense($shopLicense);
        if (!$shop) {
            $this->logger->warning('Shop not found for webhook', [
                'shop_license' => $shopLicense
            ]);

            $this->eventDispatcher->dispatch(new TransactionFailedEvent(
                $webhookDto->transactionId,
                'Shop not found',
                $context
            ));
            return;
        }

        $paymentMethod = $this->shopPaymentMethodRepository->findActiveOneByShopAndPaymentMethodId(
            $shop,
            (int) $webhookDto->paymentId
        );

        if (!$paymentMethod) {
            $this->eventDispatcher->dispatch(new TransactionFailedEvent(
                $webhookDto->transactionId,
                'Payment method not found',
                $context
            ));
            return;
        }

        try {
            $transaction = $this->transactionFactory->createOrderTransaction(
                $paymentMethod,
                $webhookDto->transactionId,
                $webhookDto->currencyId,
                $webhookDto->currencyValue,
                $webhookDto->orderId,
                $webhookDto->paymentId,
                $webhookDto->paymentData,
                $webhookDto->paymentSuccessShopLink,
                $webhookDto->paymentFailShopLink
            );

            $this->transactionRepository->save($transaction);

            $this->eventDispatcher->dispatch(new TransactionCreatedEvent(
                $transaction,
                [
                    'transaction_id' => $webhookDto->transactionId,
                    'payment_method_id' => $paymentMethod->getId(),
                    'shop_id' => $shop->getId()
                ]
            ));
        } catch (\Throwable $e) {
            $this->logger->error('Error processing transaction webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                ...$context
            ]);

            $this->eventDispatcher->dispatch(new TransactionFailedEvent(
                $webhookDto->transactionId,
                $e->getMessage(),
                $context
            ));
        }
    }

    public function processOrderRefundWebhook(OrderRefundWebhookDto $webhookDto, string $shopLicense): void
    {
        $context = [
            'refund_id' => $webhookDto->refundId,
            'transaction_id' => $webhookDto->transactionId,
            'shop_license' => $shopLicense
        ];

        $this->logger->info('Processing order refund webhook', $context);

        $repository = $this->entityManager->getRepository(\App\Entity\Transaction::class);
        $existingTransaction = $repository->findOneBy(['externalTransactionId' => $webhookDto->transactionId]);

        if ($existingTransaction) {
            $this->logger->info('Refund transaction already exists, skipping', [
                'transaction_id' => $webhookDto->transactionId
            ]);
            return;
        }

        $shop = $this->shopAppInstallationRepository->findOneByShopLicense($shopLicense);
        if (!$shop) {
            $this->logger->warning('Shop not found for webhook', [
                'shop_license' => $shopLicense
            ]);
            return;
        }

        $this->logger->warning('Refund webhook processing not fully implemented', [
            'refund_id' => $webhookDto->refundId,
            'shop_license' => $shopLicense
        ]);
    }

    /**
     * Checks if transaction with given ID already exists
     */
    private function transactionExists(string $transactionId): bool
    {
        $existingTransaction = $this->transactionRepository->findOneByExternalTransactionId($transactionId);
        if ($existingTransaction) {
            $this->logger->info('Transaction already exists, skipping', [
                'transaction_id' => $transactionId
            ]);
            return true;
        }

        return false;
    }
}
