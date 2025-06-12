<?php

namespace App\Service\OAuth;

use App\Service\Event\AppStoreLifecycleEvent;
use App\Service\OAuth\Authentication\AuthenticationServiceInterface;
use App\Service\OAuth\Factory\ShopFactoryInterface;
use App\Service\OAuth\Persistence\ShopPersistenceServiceInterface;
use App\Service\OAuth\Token\TokenManagerInterface;
use DreamCommerce\Component\ShopAppstore\Api\Http\ShopClient;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use Psr\Log\LoggerInterface;

class OAuthService
{
    private LoggerInterface $logger;
    private ShopClient $shopClient;
    private TokenManagerInterface $tokenManager;
    private ShopFactoryInterface $shopFactory;
    private AuthenticationServiceInterface $authenticationService;
    private ShopPersistenceServiceInterface $shopPersistenceService;

    public function __construct(
        LoggerInterface $logger,
        ShopClient $shopClient,
        TokenManagerInterface $tokenManager,
        ShopFactoryInterface $shopFactory,
        AuthenticationServiceInterface $authenticationService,
        ShopPersistenceServiceInterface $shopPersistenceService
    ) {
        $this->logger = $logger;
        $this->shopClient = $shopClient;
        $this->tokenManager = $tokenManager;
        $this->shopFactory = $shopFactory;
        $this->authenticationService = $authenticationService;
        $this->shopPersistenceService = $shopPersistenceService;
    }

    /**
     * Get shop client for API requests
     */
    public function getShopClient(): ShopClient
    {
        return $this->shopClient;
    }

    /**
     * Get token manager service
     */
    public function getTokenManager(): TokenManagerInterface
    {
        return $this->tokenManager;
    }

    /**
     * Get shop factory service
     */
    public function getShopFactory(): ShopFactoryInterface
    {
        return $this->shopFactory;
    }

    /**
     * Get authentication service
     */
    public function getAuthenticationService(): AuthenticationServiceInterface
    {
        return $this->authenticationService;
    }

    /**
     * Authorize with OAuth and return token data
     *
     * @param AppStoreLifecycleEvent $event Object containing shop data and authorization
     * @return array{access_token: string, refresh_token: string, expires_at: \DateTimeInterface|null}|null
     */
    public function authenticate(AppStoreLifecycleEvent $event): ?array
    {
        $result = $this->authenticationService->processAuthentication($event);
        if (!$result) {
            return null;
        }

        return $result['token_data'];
    }

    /**
     * Updates application version for existing shop
     *
     * @param AppStoreLifecycleEvent $event Event containing shop data and version information
     * @return bool True if update was successful, false otherwise
     */
    public function updateApplicationVersion(AppStoreLifecycleEvent $event): bool
    {
        $shopData = [
            'id' => $event->shopId,
            'shop_url' => $event->shopUrl,
            'version' => $event->version
        ];

        try {
            $shop = $this->shopFactory->getOAuthShop($shopData);
            return $this->shopPersistenceService->updateApplicationVersion($shop, $event);
        } catch (\Exception $e) {
            $this->logger->error('Error updating application version', [
                'shop_id' => $event->shopId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get shop instance by shop data
     *
     * @param array $shopData Shop data (must contain 'id' and 'shop_url')
     * @return OAuthShop|null Shop instance or null on error
     */
    public function getShop(array $shopData): ?OAuthShop
    {
        try {
            return $this->shopFactory->getOAuthShop($shopData);
        } catch (\Exception $e) {
            $this->logger->error('Error getting shop instance', [
                'shop_id' => $shopData['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
