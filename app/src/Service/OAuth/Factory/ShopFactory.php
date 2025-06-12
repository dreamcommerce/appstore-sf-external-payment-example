<?php

namespace App\Service\OAuth\Factory;

use App\Repository\ShopInstalledRepository;
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

    /**
     * Creates OAuthShop instance based on provided data
     *
     * @param array $shopData Shop data (must contain shop_id)
     * @return OAuthShop
     * @throws \InvalidArgumentException when required shop_id is missing
     */
    public function createOAuthShop(array $shopData): OAuthShop
    {
        if (empty($shopData['id'])) {
            throw new \InvalidArgumentException('Shop ID is required for creating OAuthShop instance');
        }

        $dateTimeFactory = new DateTimeFactory();
        $shop = new OAuthShop($shopData, $dateTimeFactory);
        $shop->setApplication($this->applicationFactory->createApplication());

        // Set shop URL if provided
        if (isset($shopData['shop_url'])) {
            $shop->setUri($this->shopClient->getHttpClient()->createUri($shopData['shop_url']));
        }

        return $shop;
    }

    /**
     * Returns OAuthShop instance for the given shop data
     * Will create a new instance if the shop is not found in the repository
     *
     * @param array $shopData Shop data (must contain shop_id)
     * @return OAuthShop
     * @throws \InvalidArgumentException when required shop_id is missing
     */
    public function getOAuthShop(array $shopData): OAuthShop
    {
        if (empty($shopData['id'])) {
            throw new \InvalidArgumentException('Shop ID is required for retrieving OAuthShop instance');
        }

        $shopId = $shopData['id'];
        $shopInstalled = $this->shopInstalledRepository->findOneBy(['shop' => $shopId]);

        if (!$shopInstalled) {
            $this->logger->warning('Shop not found for the given ID', ['id' => $shopId]);
            return $this->createOAuthShop($shopData);
        }

        if (!isset($shopData['shop_url'])) {
            $shopData['shop_url'] = $shopInstalled->getShopUrl();
        }

        $shop = $this->createOAuthShop($shopData);
        $tokensJson = $shopInstalled->getTokens();
        if ($tokensJson) {
            try {
                $tokensData = json_decode($tokensJson, true, 512, JSON_THROW_ON_ERROR);

                if (isset($tokensData['access_token'], $tokensData['refresh_token'])) {
                    $token = new Token();
                    $token->setAccessToken($tokensData['access_token']);
                    $token->setRefreshToken($tokensData['refresh_token']);

                    // Setting expiration date if available
                    if (isset($tokensData['expires_at']) && $tokensData['expires_at']) {
                        $expiresAt = new \DateTime($tokensData['expires_at']);
                        $token->setExpiresAt($expiresAt);
                    }

                    $shop->setToken($token);

                    $this->logger->debug('Token data retrieved from repository', [
                        'shop_id' => $shopId,
                        'shop_url' => $shopData['shop_url'] ?? null,
                        'has_access_token' => true
                    ]);
                }
            } catch (\Exception $e) {
                $this->logger->error('Error while decoding token data', [
                    'shop_id' => $shopId,
                    'shop_url' => $shopData['shop_url'] ?? null,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            $this->logger->debug('No saved token data found for shop', [
                'shop_id' => $shopId,
                'shop_url' => $shopData['shop_url'] ?? null
            ]);
        }

        return $shop;
    }
}
