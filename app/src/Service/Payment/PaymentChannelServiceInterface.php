<?php

declare(strict_types=1);

namespace App\Service\Payment;

use App\ValueObject\ChannelData;

interface PaymentChannelServiceInterface
{
    public function getChannelsForPayment(string $shopCode, int $paymentId): array;

    public function createChannel(string $shopCode, int $paymentId, ChannelData $channelData, string $locale): void;

    public function getChannel(string $shopCode, int $channelId, int $paymentId, string $locale): ?array;

    public function updateChannel(string $shopCode, int $paymentId, ChannelData $channelData): void;

    public function deleteChannel(string $shopCode, int $channelId, int $paymentId): void;
}
