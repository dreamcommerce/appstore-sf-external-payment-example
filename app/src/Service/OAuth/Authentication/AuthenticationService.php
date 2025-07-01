<?php

namespace App\Service\OAuth\Authentication;

use App\Service\Event\AppStoreLifecycleEvent;
use App\Service\OAuth\Factory\AuthenticatorFactoryInterface;
use App\Service\OAuth\Factory\ShopFactoryInterface;
use App\Service\OAuth\Persistence\ShopPersistenceServiceInterface;
use App\Service\Shop\Shop;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use Psr\Log\LoggerInterface;

class AuthenticationService implements AuthenticationServiceInterface
{
    private LoggerInterface $logger;
    private AuthenticatorFactoryInterface $authenticatorFactory;
    private ShopFactoryInterface $shopFactory;
    private ShopPersistenceServiceInterface $shopPersistenceService;

    public function __construct(
        LoggerInterface $logger,
        AuthenticatorFactoryInterface $authenticatorFactory,
        ShopFactoryInterface $shopFactory,
        ShopPersistenceServiceInterface $shopPersistenceService,
    ) {
        $this->logger = $logger;
        $this->authenticatorFactory = $authenticatorFactory;
        $this->shopFactory = $shopFactory;
        $this->shopPersistenceService = $shopPersistenceService;
    }

    /**
     * Perform authentication for the given shop
     *
     * @param OAuthShop $shop Shop to authenticate
     * @throws \Exception on authentication failure
     */
    public function authenticate(OAuthShop $shop): void
    {
        if ($shop->isAuthenticated()) {
            $this->logger->info('Shop is already authenticated');
            return;
        }

        $authenticator = $this->authenticatorFactory->createAuthenticator();
        $authenticator->authenticate($shop);
        $this->logger->info('OAuth authentication successful');
    }

    /**
     * Process full authentication flow for the given event
     *
     * @param AppStoreLifecycleEvent $event Event containing shop data
     * @throws \Exception on failure
     */
    public function processAuthentication(AppStoreLifecycleEvent $event): void
    {
        try {
            $shopData = $this->prepareShopDataFromEvent($event);
            $shop = $this->shopFactory->getOAuthShop($shopData);

            if ($shop->isAuthenticated()) {
                $this->logger->info('Shop is already authenticated');
                return;
            }

            $this->authenticate($shop);
            $this->shopPersistenceService->saveShopInstalled($shop, $event->authCode);
        } catch (\Exception $e) {
            $this->logger->error('Authentication process error', [
                'shop_id' => $event->shopId,
                'shop_url' => $event->shopUrl,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Prepares ShopData object based on the event
     */
    private function prepareShopDataFromEvent(AppStoreLifecycleEvent $event): Shop
    {
        $this->logger->debug('Preparing shop data from event', [
            'id' => $event->shopId,
            'shop_url' => $event->shopUrl,
            'version' => $event->version,
            'has_auth_code' => !empty($event->authCode),
        ]);

        return Shop::fromEvent($event);
    }
}