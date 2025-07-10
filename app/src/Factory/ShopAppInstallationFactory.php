<?php

namespace App\Factory;

use App\Entity\ShopAppInstallation;
use App\Entity\ShopAppToken;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;

class ShopAppInstallationFactory
{
    public function fromOAuthShop(OAuthShop $shop, string $authCode): ShopAppInstallation
    {
        return new ShopAppInstallation(
            $shop->getId(),
            $shop->getUri(),
            $shop->getVersion(),
            $authCode
        );
    }

    public function createToken(ShopAppInstallation $shopAppInstallation, array $tokenData): ShopAppToken
    {
        return new ShopAppToken(
            $shopAppInstallation,
            $tokenData['access_token'],
            $tokenData['refresh_token'],
            new \DateTimeImmutable($tokenData['expires_at']),
            true
        );
    }
}
