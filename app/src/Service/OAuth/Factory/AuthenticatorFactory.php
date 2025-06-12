<?php

namespace App\Service\OAuth\Factory;

use DreamCommerce\Component\ShopAppstore\Api\Authenticator\OAuthAuthenticator;
use DreamCommerce\Component\ShopAppstore\Api\Authenticator\OAuthAuthenticatorInterface;
use DreamCommerce\Component\ShopAppstore\Api\Http\ShopClient;

class AuthenticatorFactory implements AuthenticatorFactoryInterface
{
    private ShopClient $shopClient;

    public function __construct(ShopClient $shopClient)
    {
        $this->shopClient = $shopClient;
    }

    /**
     * Creates OAuthAuthenticator instance
     */
    public function createAuthenticator(): OAuthAuthenticatorInterface
    {
        return new OAuthAuthenticator($this->shopClient);
    }
}
