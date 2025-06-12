<?php

namespace App\Service\OAuth\Persistence;

use App\Entity\ShopInstalled;
use App\Repository\ShopInstalledRepository;
use App\Service\Event\AppStoreLifecycleEvent;
use App\Service\OAuth\Token\TokenManagerInterface;
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
     * @return bool True if shop was saved successfully
     */
    public function saveShopInstalled(OAuthShop $shop, string $authCode): bool
    {
        if (!$shop->getToken()) {
            $this->logger->warning('Cannot save shop: Token is missing', [
                'shop_id' => $shop->getId()
            ]);
            return false;
        }

        try {
            $shopInstalled = new ShopInstalled();
            $shopInstalled->setShop($shop->getId() ?? '');
            $shopInstalled->setShopUrl((string)$shop->getUri());
            $shopInstalled->setAuthCode($authCode);
            $shopInstalled->setApplicationVersion($shop->getVersion() ?? 1);

            $tokenData = [
                'access_token'  => $this->tokenManager->getAccessToken($shop),
                'refresh_token' => $this->tokenManager->getRefreshToken($shop),
                'expires_at'    => $this->tokenManager->getExpiresAt($shop) ?
                    $this->tokenManager->getExpiresAt($shop)->format('c') : null,
            ];
            $shopInstalled->setTokens(json_encode($tokenData));

            $this->shopInstalledRepository->save($shopInstalled);

            $this->logger->info('Shop installation data saved successfully', [
                'shop_id' => $shop->getId(),
                'shop_url' => $shop->getUri()
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error while saving shop installation data', [
                'shop_url' => $shop->getUri(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Updates application version for existing shop installation
     *
     * @param OAuthShop $shop Shop instance to update
     * @param AppStoreLifecycleEvent $event Event containing shop data and version information
     * @return bool True if update was successful, false otherwise
     */
    public function updateApplicationVersion(OAuthShop $shop, AppStoreLifecycleEvent $event): bool
    {
        try {
            $shopId = $shop->getId();
            $shopInstalled = $this->shopInstalledRepository->findOneBy(['shop' => $shopId]);

            if (!$shopInstalled) {
                $this->logger->warning('Cannot update version: Shop not found in repository', [
                    'shop_id' => $shopId
                ]);
                return false;
            }

            $shopInstalled->setApplicationVersion($event->version ?? $shop->getVersion() ?? 1);

            if ($shop->getToken()) {
                $tokenData = [
                    'access_token'  => $this->tokenManager->getAccessToken($shop),
                    'refresh_token' => $this->tokenManager->getRefreshToken($shop),
                    'expires_at'    => $this->tokenManager->getExpiresAt($shop) ?
                        $this->tokenManager->getExpiresAt($shop)->format('c') : null,
                ];
                $shopInstalled->setTokens(json_encode($tokenData));
            }

            $this->shopInstalledRepository->save($shopInstalled);

            $this->logger->info('Application version updated successfully', [
                'shop_id' => $shopId,
                'shop_url' => $event->shopUrl,
                'version' => $event->version ?? $shop->getVersion() ?? 1
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error while updating application version', [
                'shop_id' => $event->shopId,
                'shop_url' => $event->shopUrl,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }
}
