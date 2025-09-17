<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Dto\Webhook\OrderRefundWebhookDto;
use App\Dto\Webhook\OrderTransactionWebhookDto;
use App\Message\ProcessWebhookMessage;
use App\Service\Payment\WebhookServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles webhook processing messages asynchronously
 */
#[AsMessageHandler]
class ProcessWebhookMessageHandler
{
    public function __construct(
        private readonly WebhookServiceInterface $webhookService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ProcessWebhookMessage $message): void
    {
        $webhookType = $message->getWebhookType();
        $shopLicense = $message->getShopLicense();
        $payload = $message->getPayload();

        $this->logger->info('Processing webhook asynchronously', [
            'type' => $webhookType,
            'shop_license' => $shopLicense
        ]);

        try {
            match ($webhookType) {
                'order-transaction.create' => $this->processOrderTransaction($payload, $shopLicense),
                'order-refund.create' => $this->processOrderRefund($payload, $shopLicense),
                default => throw new \InvalidArgumentException("Unknown webhook type: {$webhookType}")
            };
        } catch (\Throwable $e) {
            $this->logger->error('Error processing webhook', [
                'type' => $webhookType,
                'shop_license' => $shopLicense,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function processOrderTransaction(array $payload, string $shopLicense): void
    {
        $webhookDto = OrderTransactionWebhookDto::fromArray($payload);
        $this->webhookService->processOrderTransactionWebhook($webhookDto, $shopLicense);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function processOrderRefund(array $payload, string $shopLicense): void
    {
        $webhookDto = OrderRefundWebhookDto::fromArray($payload);
        $this->webhookService->processOrderRefundWebhook($webhookDto, $shopLicense);
    }
}
