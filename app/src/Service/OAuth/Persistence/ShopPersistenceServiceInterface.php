<?php

namespace App\Service\OAuth\Persistence;

use App\Service\Event\AppStoreLifecycleEvent;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;

interface ShopPersistenceServiceInterface
{
    /**
     * Save shop installation data to repository
     *
     * @param OAuthShop $shop Shop instance to save
     * @param string $authCode Authorization code from the event
     * @return bool True if shop was saved successfully
     */
    public function saveShopInstalled(OAuthShop $shop, string $authCode): bool;

    /**
     * Updates application version for existing shop installation
     *
     * @param OAuthShop $shop Shop instance to update
     * @param AppStoreLifecycleEvent $event Event containing shop data and version information
     * @return bool True if update was successful, false otherwise
     */
    public function updateApplicationVersion(OAuthShop $shop, AppStoreLifecycleEvent $event): bool;
}
