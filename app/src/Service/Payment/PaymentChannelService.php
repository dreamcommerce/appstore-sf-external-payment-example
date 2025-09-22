<?php

declare(strict_types=1);

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
        $shopData = $this->getShopDataOrThrow($shopCode);
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
        $shopData = $this->getShopDataOrThrow($shopCode);
        $channelResource = new PaymentChannelResource($shopData['shopClient']);
        $channelResource->insert($shopData['oauthShop'], $channelData->toApiArray(), ['payment_id' => $paymentId]);
    }

    public function getChannel(string $shopCode, int $channelId, int $paymentId, string $locale): ?array
    {
        $shopData = $this->getShopDataOrThrow($shopCode);
        $channelResource = new PaymentChannelResource($shopData['shopClient']);
        $channel = $channelResource->find($shopData['oauthShop'], $channelId, ['payment_id' => $paymentId]);
        
        if (!$channel) {
            return null;
        }
        
        $data = $channel->getData();
        $channelData = ChannelData::fromArray($data);
        if (!$channelData->hasTranslationForLocale($locale)) {
            $this->logger->warning('Translation not found for locale', [
                'shop_code' => $shopCode,
                'channel_id' => $channelId,
                'locale' => $locale
            ]);
        }
        
        return $channelData->toArray();
    }

    public function updateChannel(string $shopCode, int $paymentId, ChannelData $channelData): void
    {
        $shopData = $this->getShopDataOrThrow($shopCode);
        $channelResource = new PaymentChannelResource($shopData['shopClient']);
        $channelResource->update($shopData['oauthShop'], $channelData->getChannelId(), $channelData->toApiArray(), ['payment_id' => $paymentId]);
    }

    public function deleteChannel(string $shopCode, int $channelId, int $paymentId): void
    {
        $shopData = $this->getShopDataOrThrow($shopCode);
        $channelResource = new PaymentChannelResource($shopData['shopClient']);
        $channelResource->delete($shopData['oauthShop'], $channelId, ['payment_id' => $paymentId]);
    }

    private function getShopDataOrThrow(string $shopCode): array
    {
        $shopData = $this->shopContextService->getShopAndClient($shopCode);
        if (!$shopData) {
            throw new \RuntimeException('Shop not found');
        }
        return $shopData;
    }
}
