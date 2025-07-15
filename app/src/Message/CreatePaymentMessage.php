<?php

namespace App\Message;

use App\ValueObject\PaymentData;

class CreatePaymentMessage
{
    private string $shopCode;
    private PaymentData $paymentData;

    public function __construct(string $shopCode, PaymentData $paymentData)
    {
        $this->shopCode = $shopCode;
        $this->paymentData = $paymentData;
    }

    public function getShopCode(): string
    {
        return $this->shopCode;
    }

    public function getPaymentData(): PaymentData
    {
        return $this->paymentData;
    }
}
