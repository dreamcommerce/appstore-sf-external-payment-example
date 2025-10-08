<?php

declare(strict_types=1);

namespace App\Event;

use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a shop has been successfully authenticated
 */
class ShopAuthenticatedEvent extends Event
{
    public const NAME = 'app.shop.authenticated';

    private OAuthShop $shop;
    private string $authCode;

    public function __construct(OAuthShop $shop, string $authCode)
    {
        $this->shop = $shop;
        $this->authCode = $authCode;
    }

    public function getShop(): OAuthShop
    {
        return $this->shop;
    }

    public function getAuthCode(): string
    {
        return $this->authCode;
    }
}
