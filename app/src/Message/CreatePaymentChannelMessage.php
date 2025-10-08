<?php

declare(strict_types=1);

namespace App\Message;

use App\ValueObject\ChannelData;

class CreatePaymentChannelMessage
{
    private string $shopCode;
    private int $paymentId;
    private ChannelData $channelData;
    private string $locale;

    public function __construct(
        string $shopCode,
        int $paymentId,
        ChannelData $channelData,
        string $locale = 'pl_PL'
    ) {
        $this->shopCode = $shopCode;
        $this->paymentId = $paymentId;
        $this->channelData = $channelData;
        $this->locale = $locale;
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

    public function getLocale(): string
    {
        return $this->locale;
    }
}
