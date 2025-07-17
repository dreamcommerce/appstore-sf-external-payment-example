<?php

namespace App\Service\Payment;

interface PaymentChannelServiceInterface
{
    /**
     * @param string $shopCode
     * @param int $paymentId
     * @return array
     */
    public function getChannelsForPayment(string $shopCode, int $paymentId): array;

    /**
     * @param string $shopCode
     * @param int $paymentId
     * @param string $type
     * @param string $applicationChannelId
     * @param string $name
     * @param string $description
     * @return bool
     */
    public function createChannelForPayment(string $shopCode, int $paymentId, string $type, string $applicationChannelId, string $name, string $description): bool;
}
