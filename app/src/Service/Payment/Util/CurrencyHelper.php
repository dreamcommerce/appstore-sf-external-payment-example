<?php

namespace App\Service\Payment\Util;

use DreamCommerce\Component\ShopAppstore\Api\Http\ShopClient;
use DreamCommerce\Component\ShopAppstore\Api\Resource\CurrencyResource;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use Psr\Log\LoggerInterface;

class CurrencyHelper
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns an array of currency details for the given currency IDs.
     * Each currency detail is an array with the following keys:
     * - id: The currency ID.
     * - name: The currency name.
     * - symbol: The currency symbol.
     * - format: The currency format.
     * - decimal_digits: The number of decimal digits.
     * - decimal_separator: The decimal separator.
     * - thousand_separator: The thousand separator.
     *
     * @param ShopClient $shopClient
     * @param OAuthShop $oauthShop
     * @param array $currencyIds
     * @return array[] An array of currency detail arrays.
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
                $currency = $currencyResource->find($oauthShop, $currencyId);
                $currencies[] = $currency->getData();
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to fetch currency details', [
                    'currency_id' => $currencyId,
                    'exception' => $e->getMessage()
                ]);
            }
        }
        return $currencies;
    }

    public function getAllCurrencies(ShopClient $shopClient, OAuthShop $oauthShop): array
    {
        try {
            $currencyResource = new CurrencyResource($shopClient);
            $result = $currencyResource->findAll($oauthShop);

            $currencies = [];
            foreach ($result as $currencyItem) {
                $currencies[] = $currencyItem->getData();
            }

            return $currencies;
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to fetch all currencies', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function mapCurrencyIdsToSupportedCurrencies(ShopClient $shopClient, OAuthShop $oauthShop, array $currencyIds): array
    {
        if (empty($currencyIds)) {
            return [];
        }

        $supportedCurrencies = [];
        $currenciesDetails = $this->getCurrenciesDetails($shopClient, $oauthShop, $currencyIds);

        foreach ($currenciesDetails as $currency) {
            if (isset($currency['name'])) {
                $supportedCurrencies[] = $currency['name'];
            }
        }

        return $supportedCurrencies;
    }
}
