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
                $currency = $currencyResource->find($oauthShop, $currencyId);
                $currencies[] = $currency->getData();
            } catch (\Throwable $e) {
                $this->logger->warning('Nie udało się pobrać waluty', [
                    'currency_id' => $currencyId,
                    'exception' => $e->getMessage()
                ]);
            }
        }
        return $currencies;
    }
}
