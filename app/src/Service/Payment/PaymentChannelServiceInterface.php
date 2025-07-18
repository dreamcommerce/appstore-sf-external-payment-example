<?php

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
     * @return bool
     */
    public function createChannel(string $shopCode, int $paymentId, ChannelData $channelData, string $locale): bool;

    /**
     * @param string $shopCode
     * @param int $channelId
     * @param int $paymentId
     * @param string $locale
     * @return ChannelData|null
     */
    public function getChannel(string $shopCode, int $channelId, int $paymentId, string $locale): ?ChannelData;

    /**
     * @param string $shopCode
     * @param int $paymentId
     * @param ChannelData $channelData
     * @return bool
     */
    public function updateChannel(string $shopCode, int $paymentId, ChannelData $channelData): bool;

    /**
     * @param string $shopCode
     * @param int $channelId
     * @param int $paymentId
     * @return bool
     */
    public function deleteChannel(string $shopCode, int $channelId, int $paymentId): bool;
}
