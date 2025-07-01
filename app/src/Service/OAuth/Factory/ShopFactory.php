<?php

namespace App\Service\OAuth\Factory;

use App\Entity\ShopInstalled;
use App\Repository\ShopInstalledRepository;
use App\Service\Shop\Shop;
use DreamCommerce\Component\Common\Factory\DateTimeFactory;
use DreamCommerce\Component\ShopAppstore\Api\Http\ShopClient;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use DreamCommerce\Component\ShopAppstore\Model\Token;
use Psr\Log\LoggerInterface;

class ShopFactory implements ShopFactoryInterface
{
    private LoggerInterface $logger;
    private ShopClient $shopClient;
    private ShopInstalledRepository $shopInstalledRepository;
    private ApplicationFactoryInterface $applicationFactory;

    public function __construct(
        LoggerInterface $logger,
        ShopClient $shopClient,
        ShopInstalledRepository $shopInstalledRepository,
        ApplicationFactoryInterface $applicationFactory
    ) {
        $this->logger = $logger;
        $this->shopClient = $shopClient;
        $this->shopInstalledRepository = $shopInstalledRepository;
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
        $shopInstalled = $this->shopInstalledRepository->findOneBy(['shop' => (string)$shopId]);
        if (!$shopInstalled) {
            $this->logger->warning('Shop not found for the given ID', ['id' => $shopId]);
            return $this->createOAuthShop($shopData);
        }

        $shopData = new Shop(
            $shopInstalled->getShop(), $shopInstalled->getShopUrl()
        );
        $shop = $this->createOAuthShop($shopData);
        $tokensJson = $shopInstalled->getTokens();
        if (!$tokensJson) {
            $this->logger->debug('No saved token data found for shop', [
                'shop_id' => $shopId,
                'shop_url' => $shopData->getShopUrl() ?? null
            ]);
            return $shop;
        }
        try {
            $tokensData = json_decode($tokensJson, true, 512, JSON_THROW_ON_ERROR);

            if (isset($tokensData['access_token'], $tokensData['refresh_token'])) {
                $token = new Token();
                $token->setAccessToken($tokensData['access_token']);
                $token->setRefreshToken($tokensData['refresh_token']);

                if (isset($tokensData['expires_at']) && $tokensData['expires_at']) {
                    $expiresAt = new \DateTime($tokensData['expires_at']);
                    $token->setExpiresAt($expiresAt);
                }

                $shop->setToken($token);
                $this->logger->debug('Token data retrieved from repository', [
                    'shop_id' => $shopId,
                    'shop_url' => $shopData->getShopUrl() ?? null,
                    'has_access_token' => true
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error while decoding token data', [
                'shop_id' => $shopId,
                'shop_url' => $shopData->getShopUrl() ?? null,
                'error' => $e->getMessage()
            ]);
        }
        return $shop;
    }
}
