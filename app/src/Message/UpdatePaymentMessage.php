<?php

declare(strict_types=1);

namespace App\Message;

use App\ValueObject\PaymentData;

class UpdatePaymentMessage
{
    private string $shopCode;
    private int $paymentId;
    private PaymentData $paymentData;

    public function __construct(string $shopCode, int $paymentId, PaymentData $paymentData)
    {
        $this->shopCode = $shopCode;
        $this->paymentId = $paymentId;
        $this->paymentData = $paymentData;
    }

    public function getShopCode(): string
    {
        return $this->shopCode;
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }

    public function getPaymentData(): PaymentData
    {
        return $this->paymentData;
    }
}
