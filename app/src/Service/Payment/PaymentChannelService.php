<?php

namespace App\Service\Payment;

use App\Service\Common\ExceptionLoggingTrait;
use App\Service\Shop\ShopContextService;
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
        $shopData = $this->shopContextService->getShopAndClient($shopCode);
        if (!$shopData) {
            $this->logger->error('Shop not found when fetching payment channels', ['shop_code' => $shopCode]);
            return [];
        }
        try {
            $channelResource = new PaymentChannelResource($shopData['shopClient']);
            $channels = $channelResource->findAll($shopData['oauthShop'], ['payment_id' => $paymentId]);
            $result = [];
            foreach ($channels as $channel) {
                $result[] = $channel->getData();
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

    public function createChannelForPayment(string $shopCode, int $paymentId, string $type, string $applicationChannelId, string $name, string $description): bool
    {
        $shopData = $this->shopContextService->getShopAndClient($shopCode);
        if (!$shopData) {
            $this->logger->error('Shop not found when creating payment channel', ['shop_code' => $shopCode]);
            throw new \RuntimeException('Shop not found');
        }
        try {
            $channelResource = new PaymentChannelResource($shopData['shopClient']);
            $data = [
                'application_channel_id' => $applicationChannelId,
                'translations' => [
                    'pl_PL' => [
                        'name' => $name,
                        'additional_label_info' => $description
                    ]
                ]
            ];
            $channelResource->insert($shopData['oauthShop'], $data, ['payment_id' => $paymentId]);
            return true;
        } catch (\Throwable $e) {
            $this->logException($this->logger, $e, 'updating payment', ['shop_code' => $shopCode, 'payment_id' => $paymentId]);
            return false;
        }
    }
}
