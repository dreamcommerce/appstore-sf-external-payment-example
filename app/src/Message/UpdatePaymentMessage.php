<?php

namespace App\Message;

class UpdatePaymentMessage
{
    private string $shopCode;
    private string $paymentId;
    private array $data;

    public function __construct(string $shopCode, string $paymentId, array $data)
    {
        $this->shopCode = $shopCode;
        $this->paymentId = $paymentId;
        $this->data = $data;
    }

    public function getShopCode(): string
    {
        return $this->shopCode;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function getData(): array
    {
        return $this->data;
    }
}

