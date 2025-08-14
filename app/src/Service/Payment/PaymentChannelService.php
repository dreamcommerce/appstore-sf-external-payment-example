<?php

namespace App\Service\Payment;

use App\Service\Shop\ShopContextService;
use App\ValueObject\ChannelData;
use DreamCommerce\Component\ShopAppstore\Api\Resource\PaymentChannelResource;
use Psr\Log\LoggerInterface;

class PaymentChannelService implements PaymentChannelServiceInterface
{
    private LoggerInterface $logger;
    private ShopContextService $shopContextService;

    public function __construct(
        LoggerInterface $logger,
        ShopContextService $shopContextService
    ) {
        $this->logger = $logger;
        $this->shopContextService = $shopContextService;
    }

    public function getChannelsForPayment(string $shopCode, int $paymentId): array
    {
        $shopData = $this->getShopDataOrLogError($shopCode, 'fetching payment channels');
        if (!$shopData) {
            return [];
        }

        $channelResource = new PaymentChannelResource($shopData['shopClient']);
        $channels = $channelResource->findAll($shopData['oauthShop'], ['payment_id' => $paymentId]);
        $result = [];
        foreach ($channels as $channel) {
            $channelData = ChannelData::fromArray($channel->getData());
            $result[] = $channelData->toArray();
        }
        return $result;
    }

    public function createChannel(string $shopCode, int $paymentId, ChannelData $channelData, string $locale): void
    {
        $shopData = $this->getShopDataOrLogError($shopCode, 'creating payment channel');
        if (!$shopData) {
            throw new \RuntimeException('Shop not found');
        }

        $channelResource = new PaymentChannelResource($shopData['shopClient']);
        $channelResource->insert($shopData['oauthShop'], $channelData->toApiArray(), ['payment_id' => $paymentId]);

        $this->logger->info('Payment channel created successfully', [
            'shop_code' => $shopCode,
            'payment_id' => $paymentId,
            'locale' => $locale,
            'channel_data' => [
                'type' => $channelData->getType(),
                'application_channel_id' => $channelData->getApplicationChannelId()
            ]
        ]);
    }

    public function getChannel(string $shopCode, int $channelId, int $paymentId, string $locale): ?ChannelData
    {
        $shopData = $this->getShopDataOrLogError($shopCode, 'fetching payment channel');
        if (!$shopData) {
            return null;
        }

        $channelResource = new PaymentChannelResource($shopData['shopClient']);
        $channel = $channelResource->find($shopData['oauthShop'], $channelId, ['payment_id' => $paymentId]);
        $data = $channel->getData();

        $channelData = ChannelData::fromArray($data);
        if (!$channelData->hasTranslationForLocale($locale)) {
            $this->logger->warning('Translation not found for locale', [
                'shop_code' => $shopCode,
                'channel_id' => $channelId,
                'locale' => $locale
            ]);
        }

        return $channelData;
    }

    public function updateChannel(string $shopCode, int $paymentId, ChannelData $channelData): void
    {
        $shopData = $this->getShopDataOrLogError($shopCode, 'updating payment channel');
        if (!$shopData) {
            throw new \RuntimeException('Shop not found');
        }

        $channelResource = new PaymentChannelResource($shopData['shopClient']);
        $channelResource->update($shopData['oauthShop'], $channelData->getChannelId(), $channelData->toApiArray(), ['payment_id' => $paymentId]);

        $this->logger->info('Payment channel updated successfully', [
            'shop_code' => $shopCode,
            'payment_id' => $paymentId,
            'channel_id' => $channelData->getChannelId(),
            'channel_data' => [
                'type' => $channelData->getType(),
                'application_channel_id' => $channelData->getApplicationChannelId()
            ]
        ]);
    }

    public function deleteChannel(string $shopCode, int $channelId, int $paymentId): void
    {
        $shopData = $this->getShopDataOrLogError($shopCode, 'deleting payment channel');
        if (!$shopData) {
            throw new \RuntimeException('Shop not found');
        }

        $channelResource = new PaymentChannelResource($shopData['shopClient']);
        $channelResource->delete($shopData['oauthShop'], $channelId, ['payment_id' => $paymentId]);

        $this->logger->info('Payment channel deleted successfully', [
            'shop_code' => $shopCode,
            'payment_id' => $paymentId,
            'channel_id' => $channelId
        ]);
    }

    private function getShopDataOrLogError(string $shopCode, string $operation): ?array
    {
        $shopData = $this->shopContextService->getShopAndClient($shopCode);
        if (!$shopData) {
            $this->logger->error(sprintf('Shop not found when %s', $operation), ['shop_code' => $shopCode]);
            return null;
        }
        return $shopData;
    }
}
