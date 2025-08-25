<?php

namespace App\Service\Shop;

use App\Domain\Shop\Model\Shop;
use App\Repository\ShopAppInstallationRepository;
use App\Service\OAuth\OAuthService;
use Psr\Log\LoggerInterface;

class ShopContextService
{
    private ShopAppInstallationRepository $shopAppInstallationRepository;
    private OAuthService $oauthService;
    private LoggerInterface $logger;

    public function __construct(
        ShopAppInstallationRepository $shopAppInstallationRepository,
        OAuthService $oauthService,
        LoggerInterface $logger
    ) {
        $this->shopAppInstallationRepository = $shopAppInstallationRepository;
        $this->oauthService = $oauthService;
        $this->logger = $logger;
    }

    /**
     * @return array{oauthShop: object, shopClient: object}|null
     */
    public function getShopAndClient(string $shopCode): ?array
    {
        $shopAppInstalled = $this->shopAppInstallationRepository->findOneBy(['shop' => $shopCode]);
        if (!$shopAppInstalled) {
            $this->logger->error('Shop not found', ['shop_code' => $shopCode]);
            return null;
        }

        $shopModel = new Shop(
            $shopAppInstalled->getShop(),
            $shopAppInstalled->getShopUrl(),
            $shopAppInstalled->getApplicationVersion(),
        );
        $oauthShop = $this->oauthService->getShop($shopModel);
        $shopClient = $this->oauthService->getShopClient();

        return [
            'oauthShop' => $oauthShop,
            'shopClient' => $shopClient,
            'shopEntity' => $shopAppInstalled
        ];
    }
}
