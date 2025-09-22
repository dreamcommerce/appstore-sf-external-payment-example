<?php

declare(strict_types=1);

namespace App\Message;

class DeletePaymentChannelMessage
{
    private string $shopCode;
    private int $channelId;
    private string $paymentId;

    public function __construct(string $shopCode, int $channelId, string $paymentId)
    {
        $this->shopCode = $shopCode;
        $this->channelId = $channelId;
        $this->paymentId = $paymentId;
    }

    public function getShopCode(): string
    {
        return $this->shopCode;
    }

    public function getChannelId(): int
    {
        return $this->channelId;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }
}
