<?php

namespace App\Service\OAuth;

use App\Service\Event\AppStoreLifecycleEvent;
use App\Service\OAuth\Authentication\AuthenticationServiceInterface;
use App\Service\OAuth\Factory\ShopFactoryInterface;
use App\Service\OAuth\Persistence\ShopPersistenceServiceInterface;
use App\Service\Shop\Shop;
use DreamCommerce\Component\ShopAppstore\Api\Http\ShopClient;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;

class OAuthService
{
    private ShopClient $shopClient;
    private ShopFactoryInterface $shopFactory;
    private AuthenticationServiceInterface $authenticationService;
    private ShopPersistenceServiceInterface $shopPersistenceService;

    public function __construct(
        ShopClient $shopClient,
        ShopFactoryInterface $shopFactory,
        AuthenticationServiceInterface $authenticationService,
        ShopPersistenceServiceInterface $shopPersistenceService
    ) {
        $this->shopClient = $shopClient;
        $this->shopFactory = $shopFactory;
        $this->authenticationService = $authenticationService;
        $this->shopPersistenceService = $shopPersistenceService;
    }

    /**
     * Get shop client for API requests
     */
    public function getShopClient(): ShopClient
    {
        return $this->shopClient;
    }

    /**
     * Authorize with OAuth for the given event
     *
     * @param AppStoreLifecycleEvent $event Object containing shop data and authorization
     * @throws \RuntimeException on authentication failure
     */
    public function authenticate(AppStoreLifecycleEvent $event): void
    {
        $this->authenticationService->processAuthentication($event);
    }

    /**
     * Updates application version for existing shop
     *
     * @param AppStoreLifecycleEvent $event Event containing shop data and version information
     * @throws \Exception on error
     */
    public function updateApplicationVersion(AppStoreLifecycleEvent $event): void
    {
        $shop = $this->shopFactory->getOAuthShop(Shop::fromEvent($event));
        $this->shopPersistenceService->updateApplicationVersion($shop, $event);
    }

    /**
     * Get shop instance by ShopData object
     *
     * @param Shop $shopData Shop data object
     *
     * @return OAuthShop Shop instance
     * @throws \Exception on error
     */
    public function getShop(Shop $shopData): OAuthShop
    {
        return $this->shopFactory->getOAuthShop($shopData);
    }
}