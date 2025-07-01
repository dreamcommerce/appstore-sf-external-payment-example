<?php

namespace App\Service\Token;

use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;

class TokenManager implements TokenManagerInterface
{
    /**
     * Get access token for the authenticated shop
     */
    public function getAccessToken(OAuthShop $shop): ?string
    {
        if (!$shop->getToken()) {
            return null;
        }
        return $shop->getToken()->getAccessToken();
    }

    /**
     * Get refresh token for the authenticated shop
     */
    public function getRefreshToken(OAuthShop $shop): ?string
    {
        if (!$shop->getToken()) {
            return null;
        }
        return $shop->getToken()->getRefreshToken();
    }

    /**
     * Get token expiration time for the authenticated shop
     */
    public function getExpiresAt(OAuthShop $shop): ?\DateTimeInterface
    {
        if (!$shop->getToken()) {
            return null;
        }
        return $shop->getToken()->getExpiresAt();
    }

    /**
     * Prepare token response data array
     */
    public function prepareTokenResponse(OAuthShop $shop): array
    {
        return [
            'access_token' => $this->getAccessToken($shop),
            'refresh_token' => $this->getRefreshToken($shop),
            'expires_at' => $this->getExpiresAt($shop) ? $this->getExpiresAt($shop)->format('c') : null,
        ];
    }
}
