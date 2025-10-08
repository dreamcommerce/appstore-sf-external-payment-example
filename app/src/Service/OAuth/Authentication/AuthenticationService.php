<?php

declare(strict_types=1);

namespace App\Service\OAuth\Authentication;

use App\Service\Event\AppStoreLifecycleEvent;
use App\OAuth\Factory\AuthenticatorFactoryInterface;
use App\Factory\ShopFactoryInterface;
use App\Domain\Shop\Model\Shop;
use App\Event\ShopAuthenticatedEvent;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AuthenticationService implements AuthenticationServiceInterface
{
    private LoggerInterface $logger;
    private AuthenticatorFactoryInterface $authenticatorFactory;
    private ShopFactoryInterface $shopFactory;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        LoggerInterface $logger,
        AuthenticatorFactoryInterface $authenticatorFactory,
        ShopFactoryInterface $shopFactory,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->logger = $logger;
        $this->authenticatorFactory = $authenticatorFactory;
        $this->shopFactory = $shopFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Perform authentication for the given shop
     *
     * @param OAuthShop $shop Shop to authenticate
     * @throws \Exception on authentication failure
     */
    public function authenticate(OAuthShop $shop): void
    {
        $this->logger->info('Starting OAuth authentication for shop', [
            'shop' => $shop->getId(),
            'shop_url' => $shop->getUri(),
            'auth_code' => $shop->getAuthCode()
        ]);

        $authenticator = $this->authenticatorFactory->createAuthenticator();
        $authenticator->authenticate($shop);
        
        $this->logger->info('OAuth authentication successful', [
            'shop' => $shop->getId(),
            'scopes' => $shop->getToken()->getScopes(),
        ]);
    }

    /**
     * Process full authentication flow for the given event
     *
     * @param AppStoreLifecycleEvent $event Event containing shop data
     * @throws \Exception on failure
     */
    public function processAuthentication(AppStoreLifecycleEvent $event): void
    {
        $shopData = Shop::fromEvent($event);
        $shop = $this->shopFactory->getOAuthShop($shopData);

        if ($shop->isAuthenticated()) {
            $this->logger->info('Already authenticated successful', [
                'shop_id' => $shop->getId(),
                'scopes' => $shop->getToken()->getScopes(),
            ]);
            return;
        }

        $this->authenticate($shop);
        $this->eventDispatcher->dispatch(
            new ShopAuthenticatedEvent($shop, $event->authCode),
            ShopAuthenticatedEvent::NAME
        );
    }
}