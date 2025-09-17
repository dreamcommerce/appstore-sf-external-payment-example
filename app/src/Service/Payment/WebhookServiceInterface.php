<?php

declare(strict_types=1);

namespace App\Service\Payment;

use App\Dto\Webhook\OrderTransactionWebhookDto;
use App\Dto\Webhook\OrderRefundWebhookDto;

interface WebhookServiceInterface
{
    /**
     * Process order transaction webhook
     */
    public function processOrderTransactionWebhook(OrderTransactionWebhookDto $webhookDto, string $shopLicense): void;

    /**
     * Process order refund webhook
     */
    public function processOrderRefundWebhook(OrderRefundWebhookDto $webhookDto, string $shopLicense): void;
}
