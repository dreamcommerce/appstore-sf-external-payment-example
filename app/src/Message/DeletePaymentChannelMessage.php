<?php

namespace App\Message;

class DeletePaymentChannelMessage
{
    private string $shopCode;
    private int $channelId;
    private int $paymentId;

    public function __construct(string $shopCode, int $channelId, int $paymentId)
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

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }
}
