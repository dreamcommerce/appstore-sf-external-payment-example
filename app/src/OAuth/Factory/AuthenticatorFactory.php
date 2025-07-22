<?php

namespace App\OAuth\Factory;

use DreamCommerce\Component\ShopAppstore\Api\Authenticator\OAuthAuthenticator;
use DreamCommerce\Component\ShopAppstore\Api\Authenticator\AuthenticatorInterface;
use DreamCommerce\Component\ShopAppstore\Api\Http\ShopClient;

class AuthenticatorFactory implements AuthenticatorFactoryInterface
{
    private ShopClient $shopClient;

    public function __construct(ShopClient $shopClient)
    {
        $this->shopClient = $shopClient;
    }

    public function createAuthenticator(): AuthenticatorInterface
    {
        return new OAuthAuthenticator($this->shopClient);
    }
}
