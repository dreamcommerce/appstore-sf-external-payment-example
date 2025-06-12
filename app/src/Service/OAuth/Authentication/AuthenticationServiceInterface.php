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
     * @return bool True if authentication was successful
     */
    public function authenticate(OAuthShop $shop): bool;

    /**
     * Process full authentication flow for the given event
     *
     * @param AppStoreLifecycleEvent $event Event containing shop data
     * @return array{shop: OAuthShop, token_data: array}|null Authentication result or null on failure
     */
    public function processAuthentication(AppStoreLifecycleEvent $event): ?array;
}
