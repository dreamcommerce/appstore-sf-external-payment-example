<?php

namespace App\Service\OAuth\Factory;

use DreamCommerce\Component\ShopAppstore\Api\Authenticator\OAuthAuthenticatorInterface;

interface AuthenticatorFactoryInterface
{
    /**
     * Creates OAuthAuthenticator instance
     */
    public function createAuthenticator(): OAuthAuthenticatorInterface;
}
