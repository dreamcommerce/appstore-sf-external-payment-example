<?php

namespace App\Factory;

use App\Domain\Shop\Model\Shop;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;

interface ShopFactoryInterface
{
    /**
     * Creates OAuthShop instance based on provided ShopData
     *
     * @param Shop $shopData Shop data object
     *
     * @return OAuthShop
     */
    public function createOAuthShop(Shop $shopData): OAuthShop;

    /**
     * Returns OAuthShop instance for the given shop data
     * Will create a new instance if the shop is not found in the repository
     *
     * @param Shop $shopData Shop data object
     *
     * @return OAuthShop
     */
    public function getOAuthShop(Shop $shopData): OAuthShop;
}