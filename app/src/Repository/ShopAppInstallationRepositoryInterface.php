<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ShopAppInstallation;

interface ShopAppInstallationRepositoryInterface
{
    /**
     * Save shop installation entity
     */
    public function save(ShopAppInstallation $shopAppInstallation, bool $flush = true): void;

    /**
     * Remove shop installation entity
     */
    public function remove(ShopAppInstallation $shopAppInstallation, bool $flush = true): void;
}
