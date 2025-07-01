<?php

namespace App\Factory;

use App\Domain\Shop\Model\Shop;
use App\Entity\ShopInstalled;
use App\Provider\ShopProvider;
use DreamCommerce\Component\Common\Factory\DateTimeFactory;
use DreamCommerce\Component\ShopAppstore\Api\Http\ShopClient;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use DreamCommerce\Component\ShopAppstore\Model\Token;
use Psr\Log\LoggerInterface;

class ShopFactory implements ShopFactoryInterface
{
    private LoggerInterface $logger;
    private ShopClient $shopClient;
    private ApplicationFactoryInterface $applicationFactory;
    private ShopProvider $shopProvider;

    public function __construct(
        LoggerInterface $logger,
        ShopClient $shopClient,
        ShopProvider $shopProvider,
        ApplicationFactoryInterface $applicationFactory
    ) {
        $this->logger = $logger;
        $this->shopClient = $shopClient;
        $this->shopProvider = $shopProvider;
        $this->applicationFactory = $applicationFactory;
    }

    public function createOAuthShop(Shop $shopData): OAuthShop
    {
        if (empty($shopData->getShopId())) {
            throw new \InvalidArgumentException('Shop ID is required for creating OAuthShop instance');
        }

        $dateTimeFactory = new DateTimeFactory();
        $shop = new OAuthShop($shopData->toArray(), $dateTimeFactory);
        $shop->setApplication($this->applicationFactory->createApplication());
        $shop->setUri($this->shopClient->getHttpClient()->createUri($shopData->getShopUrl()));

        return $shop;
    }

    public function getOAuthShop(Shop $shopData): OAuthShop
    {
        $shopId = $shopData->getShopId();
        if (empty($shopId)) {
            throw new \InvalidArgumentException('Shop ID is required for retrieving OAuthShop instance');
        }

        /** @var ShopInstalled $shopInstalled */
        $shopInstalled = $this->shopProvider->getByShopId($shopId);
        if (!$shopInstalled) {
            $this->logger->warning('Shop not found for the given ID', ['id' => $shopId]);
            return $this->createOAuthShop($shopData);
        }

        $shopFromDb = new Shop(
            $shopInstalled->getShop(), $shopInstalled->getShopUrl()
        );

        $shop = $this->createOAuthShop($shopFromDb);
        $this->addTokenToShop($shop, $shopInstalled->getTokens(), $shopId, $shopFromDb->getShopUrl());

        return $shop;
    }

    private function addTokenToShop(OAuthShop $shop, ?array $tokens, string $shopId, ?string $shopUrl): void
    {
        if (empty($tokens) ) {
            $this->logger->debug('No saved token data found for shop', [
                'shop_id' => $shopId,
                'shop_url' => $shopUrl
            ]);
            return;
        }

        if (!isset($tokens['access_token'], $tokens['refresh_token'])) {
            $this->logger->debug('Incomplete token data for shop', [
                'shop_id' => $shopId,
                'shop_url' => $shopUrl
            ]);
            return;
        }

        try {
            $token = new Token();
            $token->setAccessToken($tokens['access_token']);
            $token->setRefreshToken($tokens['refresh_token']);

            if (isset($tokens['expires_at']) && $tokens['expires_at']) {
                $expiresAt = new \DateTime($tokens['expires_at']);
                $token->setExpiresAt($expiresAt);
            }

            $shop->setToken($token);
            $this->logger->debug('Token data retrieved from repository', [
                'shop_id' => $shopId,
                'shop_url' => $shopUrl,
                'has_access_token' => true
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error while processing token data', [
                'shop_id' => $shopId,
                'shop_url' => $shopUrl,
                'error' => $e->getMessage()
            ]);
        }
    }
}
