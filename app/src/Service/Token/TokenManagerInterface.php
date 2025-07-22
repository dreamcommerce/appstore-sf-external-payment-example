<?php

namespace App\Service\Token;

use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;

interface TokenManagerInterface
{
    /**
     * Get access token for the authenticated shop
     */
    public function getAccessToken(OAuthShop $shop): ?string;

    /**
     * Get refresh token for the authenticated shop
     */
    public function getRefreshToken(OAuthShop $shop): ?string;

    /**
     * Get token expiration time for the authenticated shop
     */
    public function getExpiresAt(OAuthShop $shop): ?\DateTimeInterface;

    /**
     * Prepare token response data array
     */
    public function prepareTokenResponse(OAuthShop $shop): array;
}
