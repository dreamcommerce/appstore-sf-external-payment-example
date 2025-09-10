<?php

declare(strict_types=1);

namespace App\Service\OAuth;

use App\Domain\Shop\Model\Shop;
use App\Event\ShopVersionUpdateEvent;
use App\Service\Event\AppStoreLifecycleEvent;
use App\Factory\ShopFactoryInterface;
use DreamCommerce\Component\ShopAppstore\Api\Http\ShopClient;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OAuthService
{
    private ShopClient $shopClient;
    private ShopFactoryInterface $shopFactory;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ShopClient $shopClient,
        ShopFactoryInterface $shopFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->shopClient = $shopClient;
        $this->shopFactory = $shopFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getShopClient(): ShopClient
    {
        return $this->shopClient;
    }

    public function getShop(Shop $shopModel): OAuthShop
    {
        return $this->shopFactory->getOAuthShop($shopModel);
    }

    public function authenticate(AppStoreLifecycleEvent $event): void
    {
        $this->eventDispatcher->dispatch($event, 'app.auth.requested');
    }

    public function updateApplicationVersion(AppStoreLifecycleEvent $event): void
    {
        $shop = Shop::fromEvent($event);
        $oAuthShop = $this->shopFactory->getOAuthShop($shop);

        $this->eventDispatcher->dispatch(
            new ShopVersionUpdateEvent($oAuthShop, $shop, $event->version),
            ShopVersionUpdateEvent::NAME
        );
    }
}
