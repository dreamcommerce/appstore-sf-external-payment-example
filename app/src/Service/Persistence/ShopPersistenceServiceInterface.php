<?php

namespace App\Service\Persistence;

use App\Domain\Shop\Model\Shop;
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
    public function saveShopAppInstallation(OAuthShop $shop, string $authCode): void;

    /**
     * Updates application version for existing shop installation
     *
     * @param OAuthShop $OAuthShop
     * @param Shop      $shop Shop instance to update
     *
     * @throws \RuntimeException on error
     */
    public function updateApplicationVersion(OAuthShop $OAuthShop, Shop $shop): void;
}
