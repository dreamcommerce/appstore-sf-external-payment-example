<?php

namespace App\Service\OAuth\Authentication;

use App\Service\Event\AppStoreLifecycleEvent;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;

interface AuthenticationServiceInterface
{
    /**
     * Perform authentication for the given shop
     *
     * @param OAuthShop $shop Shop to authenticate
     * @throws \Exception on authentication failure
     */
    public function authenticate(OAuthShop $shop): void;

    /**
     * Process full authentication flow for the given event
     *
     * @param AppStoreLifecycleEvent $event Event containing shop data
     * @throws \Exception on failure
     */
    public function processAuthentication(AppStoreLifecycleEvent $event): void;
}
