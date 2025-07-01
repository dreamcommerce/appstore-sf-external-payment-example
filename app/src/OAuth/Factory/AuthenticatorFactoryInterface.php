<?php

namespace App\OAuth\Factory;

use DreamCommerce\Component\ShopAppstore\Api\Authenticator\AuthenticatorInterface;

interface AuthenticatorFactoryInterface
{
    /**
     * Creates OAuthAuthenticator instance
     */
    public function createAuthenticator(): AuthenticatorInterface;
}
