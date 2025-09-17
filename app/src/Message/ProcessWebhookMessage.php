<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message representing a webhook that should be processed
 */
class ProcessWebhookMessage
{
    /**
     * @param string $webhookType Type of the webhook (e.g. order-transaction.create)
     * @param string $shopLicense License of the shop
     * @param array<string, mixed> $payload Webhook payload data
     */
    public function __construct(
        private readonly string $webhookType,
        private readonly string $shopLicense,
        private readonly array $payload
    ) {
    }

    public function getWebhookType(): string
    {
        return $this->webhookType;
    }

    public function getShopLicense(): string
    {
        return $this->shopLicense;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
