<?php

namespace App\Service\Persistence;

use App\Domain\Shop\Model\Shop;
use App\Entity\ShopInstalled;
use App\Repository\ShopInstalledRepository;
use App\Service\Event\AppStoreLifecycleEvent;
use App\Service\Token\TokenManagerInterface;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use Psr\Log\LoggerInterface;

class ShopPersistenceService implements ShopPersistenceServiceInterface
{
    private LoggerInterface $logger;
    private ShopInstalledRepository $shopInstalledRepository;
    private TokenManagerInterface $tokenManager;

    public function __construct(
        LoggerInterface $logger,
        ShopInstalledRepository $shopInstalledRepository,
        TokenManagerInterface $tokenManager
    ) {
        $this->logger = $logger;
        $this->shopInstalledRepository = $shopInstalledRepository;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Save shop installation data to repository
     *
     * @param OAuthShop $shop Shop instance to save
     * @param string $authCode Authorization code from the event
     * @throws \RuntimeException on error
     */
    public function saveShopInstalled(OAuthShop $shop, string $authCode): void
    {
        if (!$shop->getToken()) {
            $this->logger->warning('Cannot save shop: Token is missing', [
                'shop_id' => $shop->getId()
            ]);
            throw new \RuntimeException('Cannot save shop: Token is missing for shop_id: ' . $shop->getId());
        }

        try {
            $shopInstalled = (new ShopInstalled())
                ->setShop((string)$shop->getId())
                ->setShopUrl((string)$shop->getUri())
                ->setAuthCode($authCode)
                ->setApplicationVersion(($shop->getVersion() ?? 1))
                ->setTokens($this->tokenManager->prepareTokenResponse($shop));

            $this->shopInstalledRepository->save($shopInstalled);
            $this->logger->info('Shop installation data saved successfully', [
                'shop_id' => $shop->getId(),
                'shop_url' => $shop->getUri()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error while saving shop installation data', [
                'shop_url' => $shop->getUri(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function updateApplicationVersion(OAuthShop $OAuthShop, Shop $shop): void
    {
        $shopId = $shop->getShopId();
        $shopInstalled = $this->shopInstalledRepository->findOneBy(['shop' => $shopId]);

        if (!$shopInstalled) {
            $this->logger->warning('Cannot update version: Shop not found in repository', [
                'shop_id' => $shopId
            ]);
            throw new \RuntimeException('Cannot update version: Shop not found in repository for shop_id: ' . $shopId);
        }

        try {
            $shopInstalled->setApplicationVersion($OAuthShop->getVersion() ?? $shop->getVersion() ?? 1);
            if ($OAuthShop->getToken()) {
                $shopInstalled->setTokens($this->tokenManager->prepareTokenResponse($OAuthShop));
            }

            $this->shopInstalledRepository->save($shopInstalled);

            $this->logger->info('Application version updated successfully', [
                'shop_id' => $shopId,
                'shop_url' => $OAuthShop->getUri(),
                'version' => $OAuthShop->getVersion() ?? $shop->getVersion() ?? 1
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error while updating application version', [
                'shop_id' => $shop->getShopId(),
                'shop_url' => $shop->getShopUrl(),
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
