<?php

declare(strict_types=1);

namespace App\Event;

use App\Domain\Shop\Model\Shop;
use DreamCommerce\Component\ShopAppstore\Model\OAuthShop;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a shop's application version is updated
 */
class ShopVersionUpdateEvent extends Event
{
    public const NAME = 'app.shop.version_updated';

    private OAuthShop $oAuthShop;
    private Shop $shop;
    private int $version;

    public function __construct(OAuthShop $oAuthShop, Shop $shop, int $version)
    {
        $this->oAuthShop = $oAuthShop;
        $this->shop = $shop;
        $this->version = $version;
    }

    public function getOAuthShop(): OAuthShop
    {
        return $this->oAuthShop;
    }

    public function getShop(): Shop
    {
        return $this->shop;
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}
