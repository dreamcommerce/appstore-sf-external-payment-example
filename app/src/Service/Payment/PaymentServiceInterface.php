<?php

declare(strict_types=1);

namespace App\Service\Payment;

use App\Entity\ShopAppInstallation;

interface PaymentServiceInterface
{
    public function createPayment(ShopAppInstallation $shop, string $name, array $translations, array $currencies = [], array $supportedCurrencies = []): void;

    public function updatePayment(string $shopCode, int $paymentId, array $data): void;

    public function deletePayment(string $shopCode, int $paymentId): void;

    public function getPaymentSettingsForShop(string $shopCode, string $locale): array;

    public function getPaymentById(string $shopCode, int $paymentId, string $locale): ?array;

    /**
     * Removes all payment methods for a shop
     */
    public function removeAllForShop(string $shopCode): void;
}
