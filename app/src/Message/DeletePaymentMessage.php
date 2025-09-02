<?php

declare(strict_types=1);

namespace App\Message;

class DeletePaymentMessage
{
    private string $shopCode;
    private int $paymentId;

    public function __construct(string $shopCode, int $paymentId)
    {
        $this->shopCode = $shopCode;
        $this->paymentId = $paymentId;
    }

    public function getShopCode(): string
    {
        return $this->shopCode;
    }

    public function getPaymentId(): int
    {
        return $this->paymentId;
    }
}
