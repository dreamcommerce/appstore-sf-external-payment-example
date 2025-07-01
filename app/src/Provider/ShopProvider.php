<?php

namespace App\Provider;

use App\Entity\ShopInstalled;
use App\Repository\ShopInstalledRepository;

class ShopProvider
{
    private ShopInstalledRepository $shopInstalledRepository;

    public function __construct(ShopInstalledRepository $shopInstalledRepository)
    {
        $this->shopInstalledRepository = $shopInstalledRepository;
    }

    public function getByShopId(string $shopId): ?ShopInstalled
    {
        return $this->shopInstalledRepository->findOneBy(['shop' => $shopId]);
    }

    public function getByShopUrl(string $shopUrl): ?ShopInstalled
    {
        return $this->shopInstalledRepository->findOneBy(['shopUrl' => $shopUrl]);
    }
}

