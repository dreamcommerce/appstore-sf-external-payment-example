<?php

declare(strict_types=1);

namespace App\Service\Payment;

use App\Entity\ShopAppInstallation;

interface PaymentServiceInterface
{
    /**
     * Creates a new payment for the shop
     */
    public function createPayment(ShopAppInstallation $shop, string $name, array $translations, array $currencies, array $supportedCurrencies = []): void;

    /**
     * Updates an existing payment
     */
    public function updatePayment(string $shopCode, int $paymentId, array $data): void;

    /**
     * Deletes a payment
     */
    public function deletePayment(string $shopCode, int $paymentId): void;

    /**
     * Retrieves payment settings for the shop
     */
    public function getPaymentSettingsForShop(string $shopCode, string $locale): array;

    /**
     * Retrieves payment details by ID
     */
    public function getPaymentById(string $shopCode, int $paymentId, string $locale): ?array;
}
