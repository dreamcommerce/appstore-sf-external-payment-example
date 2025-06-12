<?php

namespace App\Service\OAuth\Authentication;

use App\Service\Event\AppStoreLifecycleEvent;
use App\Service\OAuth\Factory\AuthenticatorFactoryInterface;
use App\Service\OAuth\Factory\ShopFactoryInterface;
use App\Service\OAuth\Persistence\ShopPersistenceServiceInterface;
use App\Service\OAuth\Token\TokenManagerInterface;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use Psr\Log\LoggerInterface;

class AuthenticationService implements AuthenticationServiceInterface
{
    private LoggerInterface $logger;
    private AuthenticatorFactoryInterface $authenticatorFactory;
    private ShopFactoryInterface $shopFactory;
    private ShopPersistenceServiceInterface $shopPersistenceService;
    private TokenManagerInterface $tokenManager;

    public function __construct(
        LoggerInterface $logger,
        AuthenticatorFactoryInterface $authenticatorFactory,
        ShopFactoryInterface $shopFactory,
        ShopPersistenceServiceInterface $shopPersistenceService,
        TokenManagerInterface $tokenManager
    ) {
        $this->logger = $logger;
        $this->authenticatorFactory = $authenticatorFactory;
        $this->shopFactory = $shopFactory;
        $this->shopPersistenceService = $shopPersistenceService;
        $this->tokenManager = $tokenManager;
    }

    /**
     * Perform authentication for the given shop
     *
     * @param OAuthShop $shop Shop to authenticate
     * @return bool True if authentication was successful
     */
    public function authenticate(OAuthShop $shop): bool
    {
        try {
            if ($shop->isAuthenticated()) {
                $this->logger->info('Shop is already authenticated');
                return true;
            }

            $authenticator = $this->authenticatorFactory->createAuthenticator();
            $authenticator->authenticate($shop);
            $this->logger->info('OAuth authentication successful');

            return true;
        } catch (\Exception $e) {
            $this->logger->error('OAuth authentication error', [
                'shop_id' => $shop->getId(),
                'shop_url' => $shop->getUri(),
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Process full authentication flow for the given event
     *
     * @param AppStoreLifecycleEvent $event Event containing shop data
     * @return array{shop: OAuthShop, token_data: array}|null Authentication result or null on failure
     */
    public function processAuthentication(AppStoreLifecycleEvent $event): ?array
    {
        try {
            $shopData = $this->prepareShopDataFromEvent($event);
            $shop = $this->shopFactory->getOAuthShop($shopData);

            if (!$shop->isAuthenticated()) {
                if (!$this->authenticate($shop)) {
                    return null;
                }
                $this->shopPersistenceService->saveShopInstalled($shop, $event->authCode);
            } else {
                $this->logger->info('Shop is already authenticated');
            }

            return [
                'shop' => $shop,
                'token_data' => $this->tokenManager->prepareTokenResponse($shop)
            ];
        } catch (\Exception $e) {
            $this->logger->error('Authentication process error', [
                'shop_id' => $event->shopId,
                'shop_url' => $event->shopUrl,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Prepares shop data based on the event
     */
    private function prepareShopDataFromEvent(AppStoreLifecycleEvent $event): array
    {
        $shopData = [
            'id' =>  $event->shopId,
            'shop_url' => $event->shopUrl,
            'version' => $event->version
        ];

        if (!empty($event->authCode)) {
            $shopData['auth_code'] = $event->authCode;
        }

        $this->logger->debug('Prepared shop data', [
            'id' => $event->shopId,
            'shop_url' => $event->shopUrl,
            'version' => $event->version,
            'has_auth_code' => !empty($event->authCode),
        ]);

        return $shopData;
    }
}
