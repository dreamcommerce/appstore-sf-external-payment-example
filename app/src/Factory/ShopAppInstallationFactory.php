<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\ShopAppInstallation;
use App\Entity\ShopAppToken;
use App\Service\Helper\DateTimeHelper;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;

class ShopAppInstallationFactory
{
    public function __construct(
        private readonly DateTimeHelper $dateTimeHelper
    ) {
    }

    public function fromOAuthShop(OAuthShop $shop, string $authCode): ShopAppInstallation
    {
        $uri = $shop->getUri();
        $uriString = $uri !== null ? $uri->__toString() : '';

        return new ShopAppInstallation(
            $shop->getId(),
            $uriString,
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
            $this->dateTimeHelper->createFromString($tokenData['expires_at']),
            true
        );
    }
}
