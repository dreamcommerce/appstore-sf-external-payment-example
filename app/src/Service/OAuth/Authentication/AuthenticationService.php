<?php

namespace App\Service\OAuth\Authentication;

use App\Service\Event\AppStoreLifecycleEvent;
use App\OAuth\Factory\AuthenticatorFactoryInterface;
use App\Factory\ShopFactoryInterface;
use App\Service\Persistence\ShopPersistenceServiceInterface;
use App\Domain\Shop\Model\Shop;
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
        $this->logger->info('Starting OAuth authentication for shop', [
            'shop' => $shop->getId(),
            'shop_url' => $shop->getUri(),
            'auth_code' => $shop->getAuthCode()
        ]);

        try {
            $authenticator = $this->authenticatorFactory->createAuthenticator();
            $authenticator->authenticate($shop);
            
            $this->logger->info('OAuth authentication successful', [
                'shop' => $shop->getId()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('OAuth authentication failed', [
                'shop' => $shop->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Process full authentication flow for the given event
     *
     * @param AppStoreLifecycleEvent $event Event containing shop data
     * @throws \Exception on failure
     */
    public function processAuthentication(AppStoreLifecycleEvent $event): void
    {
        $shopData = Shop::fromEvent($event);
        $shop = $this->shopFactory->getOAuthShop($shopData);

        if ($shop->isAuthenticated()) {
            $this->logger->info('Already authenticated successful', [
                'shop_id' => $shop->getId(),
            ]);
            return;
        }

        $this->authenticate($shop);
        $this->shopPersistenceService->saveShopAppInstallation(
            $shop,
            $event->authCode,
        );
    }
}