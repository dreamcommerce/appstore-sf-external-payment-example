<?php

declare(strict_types=1);

namespace App\Service\Persistence;

use App\Entity\ShopAppInstallation;

interface PaymentMethodPersistenceServiceInterface
{
    /**
     * Saves the payment method for a given store
     *
     * @param ShopAppInstallation $shop
     * @param int $paymentMethodId
     */
    public function persistPaymentMethod(ShopAppInstallation $shop, int $paymentMethodId): void;

    /**
     * Removes (soft delete) to connect payments with a given store
     *
     * @param ShopAppInstallation $shop
     * @param int $paymentMethodId
     */
    public function removePaymentMethod(ShopAppInstallation $shop, int $paymentMethodId): void;
}
