<?php

namespace App\Message;

use App\ValueObject\ChannelData;

class UpdatePaymentChannelMessage
{
    private string $shopCode;
    private int $paymentId;
    private ChannelData $channelData;

    public function __construct(
        string $shopCode,
        int $paymentId,
        ChannelData $channelData
    ) {
        $this->shopCode = $shopCode;
        $this->paymentId = $paymentId;
        $this->channelData = $channelData;
    }

    public function getShopCode(): string
    {
        return $this->shopCode;
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    public function getChannelData(): ChannelData
    {
        return $this->channelData;
    }
}
