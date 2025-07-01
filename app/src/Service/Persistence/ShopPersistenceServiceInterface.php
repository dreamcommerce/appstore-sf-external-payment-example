<?php

namespace App\Service\Persistence;

use App\Service\Event\AppStoreLifecycleEvent;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;

interface ShopPersistenceServiceInterface
{
    /**
     * Save shop installation data to repository
     *
     * @param OAuthShop $shop Shop instance to save
     * @param string $authCode Authorization code from the event
     * @throws \RuntimeException on error
     */
    public function saveShopInstalled(OAuthShop $shop, string $authCode): void;

    /**
     * Updates application version for existing shop installation
     *
     * @param OAuthShop $shop Shop instance to update
     * @param AppStoreLifecycleEvent $event Event containing shop data and version information
     * @throws \RuntimeException on error
     */
    public function updateApplicationVersion(OAuthShop $shop, AppStoreLifecycleEvent $event): void;
}
