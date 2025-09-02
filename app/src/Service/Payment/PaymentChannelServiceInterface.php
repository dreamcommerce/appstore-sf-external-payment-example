<?php

declare(strict_types=1);

namespace App\Service\Payment;

use App\ValueObject\ChannelData;

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
     * @param ChannelData $channelData
     * @param string $locale
     */
    public function createChannel(string $shopCode, int $paymentId, ChannelData $channelData, string $locale): void;

    /**
     * @param string $shopCode
     * @param int $channelId
     * @param int $paymentId
     * @param string $locale
     * @return ChannelData|null
     */
    public function getChannel(string $shopCode, int $channelId, int $paymentId, string $locale): ?array;

    /**
     * @param string $shopCode
     * @param int $paymentId
     * @param ChannelData $channelData
     */
    public function updateChannel(string $shopCode, int $paymentId, ChannelData $channelData): void;

    /**
     * @param string $shopCode
     * @param int $channelId
     * @param int $paymentId
     */
    public function deleteChannel(string $shopCode, int $channelId, int $paymentId): void;
}
