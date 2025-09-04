<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ShopAppInstallation;
use App\Entity\ShopPaymentMethod;

interface ShopPaymentMethodRepositoryInterface
{
    /**
     * Save shop payment method entity
     */
    public function save(ShopPaymentMethod $shopPaymentMethod, bool $flush = true): void;

    /**
     * Remove shop payment method entity
     */
    public function remove(ShopPaymentMethod $shopPaymentMethod, bool $flush = true): void;

    /**
     * Find active payment method by shop and paymentMethodId
     */
    public function findActiveOneByShopAndPaymentMethodId(ShopAppInstallation $shop, int $paymentMethodId): ?ShopPaymentMethod;
}
