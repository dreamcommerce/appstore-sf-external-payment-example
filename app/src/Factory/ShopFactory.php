<?php

namespace App\Factory;

use App\Domain\Shop\Model\Shop;
use App\Entity\ShopAppInstallation;
use App\Repository\ShopAppInstallationRepository;
use DreamCommerce\Component\Common\Factory\DateTimeFactory;
use DreamCommerce\Component\ShopAppstore\Api\Http\ShopClient;
use DreamCommerce\Component\ShopAppstore\Model\Application;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use DreamCommerce\Component\ShopAppstore\Model\Token;
use Psr\Log\LoggerInterface;

class ShopFactory implements ShopFactoryInterface
{
    private LoggerInterface $logger;
    private ShopClient $shopClient;
    private ShopAppInstallationRepository $shopAppInstallationRepository;
    private Application $application;

    public function __construct(
        LoggerInterface $logger,
        ShopClient $shopClient,
        ShopAppInstallationRepository $shopAppInstallationRepository,
        Application $application
    ) {
        $this->logger = $logger;
        $this->shopClient = $shopClient;
        $this->shopAppInstallationRepository = $shopAppInstallationRepository;
        $this->application = $application;
    }

    public function createOAuthShop(Shop $shopData): OAuthShop
    {
        if (empty($shopData->getShopId())) {
            throw new \InvalidArgumentException('Shop ID is required for creating OAuthShop instance');
        }

        $dateTimeFactory = new DateTimeFactory();
        $shop = new OAuthShop($shopData->toArray(), $dateTimeFactory);
        $shop->setApplication($this->application);
        $shop->setUri($this->shopClient->getHttpClient()->createUri($shopData->getShopUrl()));

        return $shop;
    }

    public function getOAuthShop(Shop $shopData): OAuthShop
    {
        $shopId = $shopData->getShopId();
        if (empty($shopId)) {
            throw new \InvalidArgumentException('Shop ID is required for retrieving OAuthShop instance');
        }

        /** @var ShopAppInstallation $shopAppInstallation */
        $shopAppInstallation = $this->shopAppInstallationRepository->findOneBy(['shop' => $shopId]);
        if (!$shopAppInstallation) {
            $this->logger->debug('Shop not found for the given ID', ['id' => $shopId]);
            return $this->createOAuthShop($shopData);
        }

        $shopFromDb = new Shop(
            $shopAppInstallation->getShop(),
            $shopAppInstallation->getShopUrl(),
            $shopAppInstallation->getApplicationVersion(),
            $shopAppInstallation->getAuthCode(),
        );
        $OAuthShop = $this->createOAuthShop($shopFromDb);

        $tokenEntity = $shopAppInstallation->getActiveToken();
        $tokenData = [
            'access_token' => $tokenEntity->getAccessToken(),
            'refresh_token' => $tokenEntity->getRefreshToken(),
            'expires_at' => $tokenEntity->getExpiresAt()?->format(
                'Y-m-d H:i:s',
            ),
        ];

        $this->applyTokenToOAuthShop($OAuthShop, $tokenData, $shopId, $shopFromDb->getShopUrl());

        return $OAuthShop;
    }

    private function applyTokenToOAuthShop(OAuthShop $shop, ?array $tokens, string $shopId, ?string $shopUrl): void
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

                if ($expiresAt < new \DateTime()) {
                    $this->logger->info('Token has expired, authentication will be required', [
                        'shop_id' => $shopId,
                        'shop_url' => $shopUrl,
                        'expired_at' => $expiresAt->format('Y-m-d H:i:s')
                    ]);
                }
            }

            $shop->setToken($token);
            $this->logger->debug('Token data successfully set for shop', [
                'shop_id' => $shopId,
                'shop_url' => $shopUrl,
                'has_access_token' => true,
                'has_refresh_token' => true,
                'has_expires_at' => isset($tokens['expires_at'])
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error while processing token data', [
                'shop_id' => $shopId,
                'shop_url' => $shopUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
