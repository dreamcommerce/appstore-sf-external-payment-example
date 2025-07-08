<?php

namespace App\Service\Payment\Util;

use DreamCommerce\Component\ShopAppstore\Api\Http\ShopClient;
use DreamCommerce\Component\ShopAppstore\Api\Resource\CurrencyResource;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;

class CurrencyHelper
{
    /**
     * Pobiera szczegóły walut na podstawie tablicy ID.
     *
     * @param ShopClient $shopClient
     * @param OAuthShop $oauthShop
     * @param array $currencyIds
     *
     * @return array
     */
    public function getCurrenciesDetails(ShopClient $shopClient, OAuthShop $oauthShop, array $currencyIds): array
    {
        if (empty($currencyIds)) {
            return [];
        }

        $currencyResource = new CurrencyResource($shopClient);
        $currencies = [];
        foreach ($currencyIds as $currencyId) {
            try {
                $currency = $currencyResource->find($oauthShop, (int)$currencyId);
                $currencies[] = $currency->getData();
            } catch (\Throwable $e) {
                // Pomijamy błędne ID walut
            }
        }
        return $currencies;
    }
}
