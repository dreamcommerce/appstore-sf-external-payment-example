<?php

namespace App\Service\OAuth\Factory;

use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;

interface ShopFactoryInterface
{
    /**
     * Creates OAuthShop instance based on provided data
     *
     * @param array $shopData Shop data (must contain shop_id)
     * @return OAuthShop
     * @throws \InvalidArgumentException when required shop_id is missing
     */
    public function createOAuthShop(array $shopData): OAuthShop;

    /**
     * Returns OAuthShop instance for the given shop data
     * Will create a new instance if the shop is not found in the repository
     *
     * @param array $shopData Shop data (must contain shop_id)
     * @return OAuthShop
     * @throws \InvalidArgumentException when required shop_id is missing
     */
    public function getOAuthShop(array $shopData): OAuthShop;
}
