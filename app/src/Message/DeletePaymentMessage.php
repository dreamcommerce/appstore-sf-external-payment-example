<?php

namespace App\Message;

class DeletePaymentMessage
{
    private string $shopCode;
    private string $paymentId;

    public function __construct(string $shopCode, string $paymentId)
    {
        $this->shopCode = $shopCode;
        $this->paymentId = $paymentId;
    }

    public function getShopCode(): string
    {
        return $this->shopCode;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }
}

