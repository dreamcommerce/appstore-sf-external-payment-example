<?php

namespace App\Service\Payment;

use App\Service\Common\ExceptionLoggingTrait;
use App\Service\Shop\ShopContextService;
use App\ValueObject\ChannelData;
use DreamCommerce\Component\ShopAppstore\Api\Resource\PaymentChannelResource;
use Psr\Log\LoggerInterface;

class PaymentChannelService implements PaymentChannelServiceInterface
{
    use ExceptionLoggingTrait;

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

        try {
            $channelResource = new PaymentChannelResource($shopData['shopClient']);
            $channels = $channelResource->findAll($shopData['oauthShop'], ['payment_id' => $paymentId]);
            $result = [];
            foreach ($channels as $channel) {
                $channelData = ChannelData::fromArray($channel->getData());
                $result[] = $channelData->toArray();
            }
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching payment channels', [
                'shop_code' => $shopCode,
                'payment_id' => $paymentId,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    public function createChannel(string $shopCode, int $paymentId, ChannelData $channelData, string $locale): bool
    {
        $shopData = $this->getShopDataOrLogError($shopCode, 'creating payment channel');
        if (!$shopData) {
            throw new \RuntimeException('Shop not found');
        }

        try {
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

            return true;
        } catch (\Throwable $e) {
            $this->logException($this->logger, $e, 'creating payment channel', [
                'shop_code' => $shopCode,
                'payment_id' => $paymentId,
                'locale' => $locale
            ]);
            return false;
        }
    }

    public function getChannel(string $shopCode, int $channelId, int $paymentId, string $locale): ?ChannelData
    {
        $shopData = $this->getShopDataOrLogError($shopCode, 'fetching payment channel');
        if (!$shopData) {
            return null;
        }

        try {
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
        } catch (\Throwable $e) {
            $this->logException($this->logger, $e, 'fetching payment channel', [
                'shop_code' => $shopCode,
                'channel_id' => $channelId,
                'payment_id' => $paymentId,
                'locale' => $locale
            ]);
            return null;
        }
    }

    public function updateChannel(string $shopCode, int $paymentId, ChannelData $channelData): bool
    {
        $shopData = $this->getShopDataOrLogError($shopCode, 'updating payment channel');
        if (!$shopData) {
            return false;
        }

        try {
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

            return true;
        } catch (\Throwable $e) {
            $this->logException($this->logger, $e, 'updating payment channel', [
                'shop_code' => $shopCode,
                'channel_id' => $channelData->getChannelId(),
                'payment_id' => $paymentId
            ]);
            return false;
        }
    }

    public function deleteChannel(string $shopCode, int $channelId, int $paymentId): bool
    {
        $shopData = $this->getShopDataOrLogError($shopCode, 'deleting payment channel');
        if (!$shopData) {
            return false;
        }

        try {
            $channelResource = new PaymentChannelResource($shopData['shopClient']);
            $channelResource->delete($shopData['oauthShop'], $channelId, ['payment_id' => $paymentId]);

            $this->logger->info('Payment channel deleted successfully', [
                'shop_code' => $shopCode,
                'payment_id' => $paymentId,
                'channel_id' => $channelId
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logException($this->logger, $e, 'deleting payment channel', [
                'shop_code' => $shopCode,
                'channel_id' => $channelId,
                'payment_id' => $paymentId
            ]);
            return false;
        }
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
